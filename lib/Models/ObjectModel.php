<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;

/**
 * Model for Kirby's fields: object
 */
class ObjectModel extends Model
{
  /**
   * Marker if this model has child fields.
   * 
   * @var Boolean
   */
  protected $hasChildFields = true;

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return Collection
   */
  protected function getValue ()
  {
    return $this->fields;
  }
}