<?php

namespace Tritrics\Tric\v1\Models;

use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Helper\UrlHelper;

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
    $parts = UrlHelper::parse($this->model->url());
    $title = $this->fields->node('title', 'value')->get();
    if (!$title) {
      $title = $parts['filename'];
    }

    $meta = new Collection();
    $meta->add('host', UrlHelper::buildHost($parts));
    $meta->add('dir', $parts['dirname']);
    $meta->add('file', $parts['basename']);
    $meta->add('name', $parts['filename']);
    $meta->add('ext', $parts['extension']);
    $meta->add('href', UrlHelper::build($parts));
    $meta->add('filetype', $this->model->type());
    $meta->add('blueprint', $this->model->template());
    $meta->add('title', $title);
    if ($this->model->type() === 'image') {
      $meta->add('width', $this->model->width());
      $meta->add('height', $this->model->height());
    }

    $res = new Collection();
    $res->add('meta', $meta);
    return $res;
  }
}
