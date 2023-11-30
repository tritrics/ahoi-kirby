<?php

namespace Tritrics\AflevereApi\v1\Blocks;

use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Data\Collection;

/** */
class DefaultModel extends Model
{
  /** */
  protected $hasChildFields = true;

  protected function getType ()
  {
    return 'block';
  }

  /** */
  protected function getProperties()
  {
    $res = new Collection();
    $res->add('block', $this->model->type());
    return $res;
  }

  /** */
  protected function getValue ()
  {
    return $this->fields;
  }
}
