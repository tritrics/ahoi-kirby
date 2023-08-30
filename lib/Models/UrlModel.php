<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\LinkService;

/** */
class UrlModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    $res->add('link', LinkService::getExtern($this->model->value()));
    return $res;
  }

  /** */
  protected function getValue ()
  {
    return $this->model->value();
  }
}
