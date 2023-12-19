<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LinkService;
use Tritrics\AflevereApi\v1\Services\FileService;

/** */
class FileModel extends Model
{
  /** */
  protected $hasChildFields = true;

  /** */
  public function __construct($model, $blueprint, $lang)
  {
    parent::__construct($model, $blueprint, $lang);
  }

  protected function getType()
  {
    return $this->model->type();
  }

  /** */
  protected function getProperties ()
  {
    $pathinfo = FileService::getPathinfo($this->model->url());

    $title = $this->fields->node('title', 'value')->get();
    if (!$title) {
      $title = $pathinfo['file'];
    }

    $meta = new Collection();
    $meta->add('dir', $pathinfo['dirname'] . '/');
    $meta->add('file', $pathinfo['file']);
    $meta->add('filename', $pathinfo['filename']);
    $meta->add('ext', $pathinfo['extension']);
    $meta->add('blueprint', $this->model->template());
    $meta->add('title', $title);
    if ($this->model->type() === 'image') {
      $meta->add('width', $this->model->width());
      $meta->add('height', $this->model->height());
    }

    $res = new Collection();
    $res->add('meta', $meta);
    $res->add('link', LinkService::getFile($pathinfo['path']));
    return $res;
  }

  /** */
  protected function getValue()
  {
    return $this->fields;
  }
}
