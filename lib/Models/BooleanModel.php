<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;

/** */
class BooleanModel extends Model
{
  /** */
  protected function getValue ()
  {
    return (float) $this->model->isTrue(); // return 0 or 1 as number
  }
}