<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LinkService;

/** */
class TelModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    $res->add('link', LinkService::getTel($this->model->value()));
    return $res;
  }

  /** */
  protected function getValue ()
  {
    
    return $this->model->value();
  }
}