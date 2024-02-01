<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;

/**
 * Model for Kirby's fields: color
 */
class ColorModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties(): Collection
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

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   */
  protected function getValue(): string
  {
    return (string) $this->model->value();
  }
}
