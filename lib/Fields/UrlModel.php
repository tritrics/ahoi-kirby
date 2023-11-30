<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LinkService;

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
