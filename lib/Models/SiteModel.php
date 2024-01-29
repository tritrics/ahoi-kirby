<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Helper\LinkHelper;


/**
 * Model for Kirby's site object
 */
class SiteModel extends Model
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
  protected function getProperties ()
  {
    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('host', $this->model->url($this->lang));
    if ($this->lang !== null) {
      $meta->add('lang', $this->lang);
      $meta->add('locale', LanguagesService::getLocale($this->lang));
    }
    $meta->add('title', $this->model->title()->get());
    $meta->add('modified',  date('c', $this->model->modified()));
    $meta->add('blueprint', 'site');

    if ($this->blueprint->has('api', 'meta')) {
      foreach ($this->blueprint->node('api', 'meta')->get() as $key => $value) {
        if (!$meta->has($key)) {
          $meta->add($key, $value);
        }
      }
    }

    $page = $this->model->homePage();
    $res->add('home', LinkHelper::getPage(
      LanguagesService::getUrl($this->lang, $page->uri($this->lang))
    ));
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return Void
   */
  protected function getValue () {
  }
}