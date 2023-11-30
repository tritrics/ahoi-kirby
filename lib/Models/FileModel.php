<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LinkService;

/** */
class FileModel extends Model
{
  /** */
  protected $hasChildFields = true;

  protected function getType()
  {
    return $this->model->type();
  }

  /** */
  protected function getProperties ()
  {
    $pathinfo = pathinfo($this->model->url());

    // Kirby confuses jpeg an jpg on images. ImageService only works with jpg!
    $ext = strtolower($pathinfo['extension']) === 'jpeg' ? 'jpg' : strtolower($pathinfo['extension']);
    $file = $pathinfo['filename'] . '.' . $ext;
    $title = $this->fields->node('title', 'value')->get();
    if (!$title) {
      $title = $file;
    }

    $meta = new Collection();
    $meta->add('dir', $pathinfo['dirname'] . '/');
    $meta->add('file', $file);
    $meta->add('filename', $pathinfo['filename']);
    $meta->add('ext', $ext);
    $meta->add('blueprint', $this->model->template());
    $meta->add('title', $title);
    if ($this->model->type() === 'image') {
      $meta->add('width', $this->model->width());
      $meta->add('height', $this->model->height());
    }

    $res = new Collection();
    $res->add('meta', $meta);
    $res->add('link', LinkService::getFile(
      $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.' . $ext
    ));
    return $res;
  }

  /** */
  protected function getValue()
  {
    return $this->fields;
  }
}
