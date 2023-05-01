<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Model;

/** */
class BlockDefaultModel extends Model
{
  /** */
  protected $hasChildFields = true;

  protected function getType ()
  {
    return 'block-' . $this->model->type();
  }

  /** */
  protected function getValue ()
  {
    return $this->fields;
  }
}
