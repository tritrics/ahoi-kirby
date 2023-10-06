<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\LanguageService;
use Tritrics\Api\Services\LinkService;


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
    $content = $this->model->content($this->lang);

    $meta = new Collection();
    $meta->add('host', $this->model->url($this->lang));
    if ($this->lang !== null) {
      $meta->add('lang', $this->lang);
      $meta->add('locale', LanguageService::getLocale($this->lang));
    }
    $meta->add('modified',  date('c', $this->model->modified()));
    $meta->add('blueprint', 'site');

    $res = new Collection();
    $res->add('meta', $meta);
    return $res;
  }

  /** */
  protected function getValue () {}
}