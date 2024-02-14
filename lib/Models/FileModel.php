<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Helper\LinkHelper;
use Tritrics\AflevereApi\v1\Services\FileService;

/**
 * Model for Kirby's file object
 */
class FileModel extends BaseModel
{
  /**
   * Marker if this model has child fields.
   * 
   * @var bool
   */
  protected $hasChildFields = true;

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties (): Collection
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
    $res->add('link', LinkHelper::getFile($pathinfo['path']));
    return $res;
  }

  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   */
  protected function getType(): string
  {
    return $this->model->type();
  }

  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue(): Collection
  {
    return $this->fields;
  }
}
