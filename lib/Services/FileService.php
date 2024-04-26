<?php

namespace Tritrics\Tric\v1\Services;

use Exception;
use Kirby\Filesystem\F;
use Kirby\Http\Header;
use Kirby\Cms\Media;
use Kirby\Exception\NotFoundException;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\LogicException;

/**
 * File functions and image handling. Creates thumbs.
 */
class FileService
{
  /**
   * Create a thumb so that it can be called by the given path.
   * Options given by filename:
   * filename[-(width)x(height)][-crop-(option)][-blur(integer)][-bw][-q(integer)].extension
   *
   * - width
   * - height
   * - cropping position
   * - blur (default false)
   * - greyscale (default false)
   * - quality (default 90)
   * 
   * @throws NotFoundException 
   * @throws InvalidArgumentException 
   * @throws LogicException 
   * @throws Exception 
   */
  public static function getImage (
    string $path,
    array $arguments,
    string $pattern,
    ?array $options = []
  ): void {
    $pathinfo = pathinfo($path);
    $filename = $pathinfo['basename'];
    $preg = "/^(.*?)(-(\d*)x(\d*))?(-crop-(top-left|top|top-right|left|center|right|bottom-left|bottom|bottom-right)+)?(-blur(\d+))?(-bw)?(-q(\d+))?\.(jpg|png)$/i";
    $res = preg_match($preg, $filename, $matches);
    if (!$res) {
      return;
    }

    // compute all parts
    $basename  = $matches[1];
    $extension = $matches[12];
    $width     = intval($matches[3]) > 0 ? intval($matches[3]) : null;
    $height    = intval($matches[4]) > 0 ? intval($matches[4]) : null;
    $crop      = str_replace('-crop-', '', $matches[5]);
    $crop      = is_string($crop) ? str_replace('-', ' ', $crop) : false;
    $blur      = intval($matches[8]) > 0 ? intval($matches[8]) : false;
    $greyscale = $matches[9] === '-bw';
    $quality   = intval($matches[11]) >= 1 && intval($matches[11]) <= 100 ? intval($matches[11]) : null;
    
    $options = [
      'autoOrient' => true,
      'crop'       => $crop,
      'blur'       => $blur,
      'grayscale'  => $greyscale,
      'height'     => $height,
      'quality'    => $quality,
      'width'      => $width
    ];

    $pattern = explode('/', $pattern);
    switch($pattern[1]) {
      case 'pages': // 'media/pages/(:all)/(:any)/(:any)'
        $model = page($arguments[0]);
        break;
      case 'site': // 'media/site/(:any)/(:any)'
        $model = site();
        break;
      case 'users': // media/users/(:any)/(:any)/(:any)
        $model = kirby()->user($arguments[0]);
        break;
    }
    if (!$model) {
      return;
    }

    // Try to get file by filename
    $file = $model->file($basename . '.' . $extension);
    if ((!$file || !$file->exists()) && $extension === 'jpg') {
      $file = $model->file($basename . '.jpeg');
    }
    if ((!$file || !$file->exists())) {
      return;
    }

    // Create thumb
    $thumb = $file->thumb($options);

    // Kirby doesn't create thumb immediately, it need's bo be
    // linked = published to public dir
    Media::link($model, $file->mediaHash(), $thumb->filename());

    // send thumb to browser
    Header::contentType(F::mime($thumb->root()));
    Header::create('Content-Length', F::size($thumb->root()));
    readfile($thumb->root());

    // Workaround with underscores in filenames: rename the thumbname (created by Kirby)
    // to the original name, so next call this method is not invoked.
    if (F::filename($thumb->root()) !== $filename) {
      F::move($thumb->root(), F::dirname($thumb->root()) . '/' . $filename, true);
    }
    exit;
  }
}
