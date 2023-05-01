<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;

/** */
class ObjectModel extends Model
{
  /** */
  protected $hasChildFields = true;

  /** */
  protected function getValue () : Collection
  {
    return $this->fields;
  }
}