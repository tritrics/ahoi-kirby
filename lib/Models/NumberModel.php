<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;

/**
 * Model for Kirby's fields: number, range
 */
class NumberModel extends Model
{
  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return number
   */
  protected function getValue ()
  {
    return (float) $this->model->value();
  }
}
