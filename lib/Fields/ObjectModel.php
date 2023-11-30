<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;

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