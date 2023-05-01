<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Model;

/** */
class ToggleModel extends Model
{
  /** */
  protected function getValue ()
  {
    return (float) $this->model->isTrue(); // return 0 or 1 as number
  }
}