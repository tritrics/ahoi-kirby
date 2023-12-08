<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;

/** */
class ObjectModel extends Model
{
  /** */
  protected $hasChildFields = true;

  /** */
  protected function getValue ()
  {
    return $this->fields;
  }
}