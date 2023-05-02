<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;

/** */
class FileModel extends Model
{
  /** */
  protected $hasChildFields = true;

  /** */
  protected function getProperties ()
  {
    $pathinfo = pathinfo($this->model->url());

    // Kirby confuses jpeg an jpg on images. ImageService only works with jpg!
    $ext = strtolower($pathinfo['extension']) === 'jpeg' ? 'jpg' : strtolower($pathinfo['extension']);

    $res = new Collection();
    $res->add('dir', $pathinfo['dirname'] . '/');
    $res->add('file', $pathinfo['filename']);
    $res->add('ext', $ext);
    $res->add('blueprint', $this->model->template());
    $res->add('isimage', $this->model->type() === 'image');
    if ($this->model->type() === 'image') {
      $res->add('width', $this->model->width());
      $res->add('height', $this->model->height());
    }

    $title = $this->fields->node('title', 'value')->get();
    if (!$title) {
      $title = $pathinfo['filename'];
    }
    $link = $res->add('link');
    $link->add('type', 'file');
    $link->add('uri', $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.' . $ext);
    $link->add('title', $title);
    return $res;
  }

  /** */
  protected function getValue()
  {
    return $this->fields;
  }
}
