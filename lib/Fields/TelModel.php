<?php

namespace Tritrics\Api\Fields;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\LinkService;

/** */
class TelModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $tel = $this->model->value();
    $tel = preg_replace('/^[+]{1,}/', '00', $tel);
    $tel = preg_replace('/[^0-9]/', '', $tel);

    $res = new Collection();
    $res->add('link', LinkService::getTel($tel));
    return $res;
  }

  /** */
  protected function getValue ()
  {
    
    return $this->model->value();
  }
}