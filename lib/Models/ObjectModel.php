<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;

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
   * Nodename for fields.
   */
  protected $valueNodeName = 'fields';

  /**
   * Get the value of model.
   */
  protected function getValue(): Collection|null
  {
    return $this->fields;
  }
}