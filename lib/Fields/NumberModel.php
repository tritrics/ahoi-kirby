<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Model;

/** */
class NumberModel extends Model
{
  /** */
  protected function getValue ()
  {
    return (float) $this->model->value();
  }
}
