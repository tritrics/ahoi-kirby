<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;

/** */
class UrlModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    $pathinfo = parse_url($this->model->value());
    $link = $res->add('link');
    $link->add('type', 'extern');
    $link->add('uri', $this->model->value());
    $link->add('title', isset($pathinfo['host']) ? $pathinfo['host'] : $this->model->value());
    return $res;
  }

  /** */
  protected function getValue ()
  {
    return $this->model->value();
  }
}
