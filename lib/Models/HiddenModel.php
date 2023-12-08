<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\GlobalService;

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