<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: hidden
 */
class HiddenModel extends BaseModel
{
  /**
   */
  public function __construct()
  {
    parent::__construct(...func_get_args());
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $value = $this->model->value();
    if (is_numeric($value)) {
      $type = 'number';
    } else if (is_bool($value)) {
      $type = 'toggle';
    } else {
      $type = 'string';
    }
    $this->add('type', $type);
    $this->add('value', TypeHelper::toChar($value));
  }
}