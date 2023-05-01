<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;

/** */
class TelModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $uri = $this->model->value();
    $uri = preg_replace('/^[+]{1,}/', '00', $uri);
    $uri = preg_replace('/[^0-9]/', '', $uri);

    $res = new Collection();
    $pathinfo = parse_url($this->model->value());
    $link = $res->add('link');
    $link->add('type', 'tel');
    $link->add('uri', 'tel:' . $uri);
    $link->add('title', $this->model->value());
    return $res;
  }

  /** */
  protected function getValue ()
  {
    
    return $this->model->value();
  }
}