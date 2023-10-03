<?php

namespace Tritrics\Api\Fields;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\GlobalService;

/** */
class OptionModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    if ($this->blueprint->node('api', 'labels')->is(true)) {
      $res->add('label', $this->getLabel($this->model->value()));
    }
    return $res;
  }

  /** */
  protected function getValue ()
  {
    return GlobalService::typecast($this->model->value());
  }
}