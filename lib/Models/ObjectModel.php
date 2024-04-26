<?php

namespace Tritrics\Tric\v1\Models;

use Tritrics\Tric\v1\Data\Collection;

/**
 * Model for Kirby's fields: object
 */
class ObjectModel extends BaseModel
{
  /**
   * Marker if this model has child fields.
   * 
   * @var bool
   */
  protected $hasChildFields = true;

  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue (): Collection
  {
    return $this->fields;
  }
}