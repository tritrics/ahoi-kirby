<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;


/** */
class SiteModel extends Model
{
  /** */
  public function __construct ($model, $blueprint, $lang)
  {
    parent::__construct($model, $blueprint, $lang);
  }
  
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    $res->add('host', $this->model->url($this->lang));
    if ($this->lang !== null) {
      $res->add('lang', $this->lang);
    }
    $res->add('modified',  date('c', $this->model->modified()));
    return $res;
  }

  /** */
  protected function getValue () {}
}