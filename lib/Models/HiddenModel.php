<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: hidden
 */
class HiddenModel extends BaseModel
{
  /**
   * Get type of this model as it's returned in response.
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
   * Get the value of model.
   */
  protected function getValue (): mixed
  {
    return TypeHelper::toChar($this->model->value());
  }
}