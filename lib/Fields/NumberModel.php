<?php

namespace Tritrics\Api\Fields;

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
