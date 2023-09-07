<?php

namespace Tritrics\Api\Fields;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\LinkService;

/** */
class EmailModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    $res->add('link', LinkService::getEmail($this->model->value()));
    return $res;
  }

  /** */
  protected function getValue ()
  {
    return $this->model->value();
  }
}
