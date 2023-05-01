<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\GlobalService;

/** */
class HiddenModel extends Model
{
  /** */
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

  /** */
  protected function getValue ()
  {
    return GlobalService::typecast($this->model->value());
  }
}