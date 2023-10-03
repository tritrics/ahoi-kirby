<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\LinkService;

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

    $meta = new Collection();
    $meta->add('dir', $pathinfo['dirname'] . '/');
    $meta->add('filename', $pathinfo['filename']);
    $meta->add('ext', $ext);
    $meta->add('blueprint', $this->model->template());
    if ($this->model->type() === 'image') {
      $meta->add('width', $this->model->width());
      $meta->add('height', $this->model->height());
    }

    $res = new Collection();
    $res->add('meta', $meta);
    $title = $this->fields->node('title', 'value')->get();
    if (!$title) {
      $title = $pathinfo['filename'];
    }
    $res->add('link', LinkService::getFile(
      $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.' . $ext,
      $title
    ));
    return $res;
  }

  /** */
  protected function getValue()
  {
    return $this->fields;
  }
}
