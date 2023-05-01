<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Model;

/** */
class NumberModel extends Model
{
  /** */
  protected function getValue ()
  {
    return (float) $this->model->value();
  }
}
