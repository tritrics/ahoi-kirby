<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LinkService;

/** */
class ColorModel extends Model
{
  /** */
  protected function getProperties()
  {
    $res = new Collection();
    $meta = $res->add('meta');
    if ($this->blueprint->has('format')) {
      $meta->add('format', $this->blueprint->node('format')->get());
    } else {
      $meta->add('format', 'hex');
    }
    if ($this->blueprint->has('format')) {
      $meta->add('alpha', $this->blueprint->node('alpha')->get());
    } else {
      $meta->add('alpha', false);
    }
    return $res;
  }

  /** */
  protected function getValue()
  {
    return $this->model->value();
  }
}
