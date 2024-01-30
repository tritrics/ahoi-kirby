<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: hidden
 */
class HiddenModel extends Model
{
  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   */
  protected function getType (): string
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
   */
  protected function getValue (): mixed
  {
    return TypeHelper::auto($this->model->value());
  }
}