<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\GlobalService;

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