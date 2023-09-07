<?php

namespace Tritrics\Api\Fields;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;

/** */
class DatetimeModel extends Model
{  
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    $res->add('datetime', date('c', strtotime($this->getValue())));
    return $res;
  }

  /** */
  protected function getValue ()
  {
    return $this->model->value();
  }
}
