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
    $meta = new Collection();
    $meta->add('host', $this->model->url($this->lang));
    if ($this->lang !== null) {
      $meta->add('lang', $this->lang);
    }
    $meta->add('modified',  date('c', $this->model->modified()));

    $res = new Collection();
    $res->add('meta', $meta);
    return $res;
  }

  /** */
  protected function getValue () {}
}