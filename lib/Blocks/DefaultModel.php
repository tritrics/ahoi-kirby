<?php

namespace Tritrics\Api\Blocks;

use Tritrics\Api\Data\Model;
use Tritrics\Api\Data\Collection;

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
