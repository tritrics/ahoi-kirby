<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\GlobalService;

/**
 * Model for Kirby's fields: hidden
 */
class HiddenModel extends Model
{
  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   * 
   * @return String 
   */
  protected function getType ()
  {
    $value = $this->getValue();
    if (is_numeric($value)) {
      return 'number';
    } else if (is_bool($value)) {
      return 'toggle';
    }
    return 'string';
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return String|Number
   */
  protected function getValue ()
  {
    return GlobalService::typecast($this->model->value());
  }
}