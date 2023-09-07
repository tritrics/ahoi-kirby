<?php

namespace Tritrics\Api\Blocks;

use Tritrics\Api\Data\Model;

/** */
class DefaultModel extends Model
{
  /** */
  protected $hasChildFields = true;

  protected function getType ()
  {
    return $this->model->type();
  }

  /** */
  protected function getValue ()
  {
    return $this->fields;
  }
}
