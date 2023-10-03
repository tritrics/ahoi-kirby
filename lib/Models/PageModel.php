<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\LanguageService;
use Tritrics\Api\Services\LinkService;


/** */
class PageModel extends Model
{
  /** */
  private $add_translations;

  /** */
  public function __construct ($model, $blueprint, $lang, $add_translations = false)
  {
    $this->add_translations = $add_translations;
    parent::__construct($model, $blueprint, $lang);
  }
  
  /** */
  protected function getProperties ()
  {
    $content = $this->model->content($this->lang);

    $meta = new Collection();
    $meta->add('id', $this->model->id());
    $meta->add('parent', $this->getParentUrl($this->lang));
    $meta->add('slug',  $this->getSlug($this->lang));
    if ($this->lang !== null) {
      $meta->add('lang', $this->lang);
      $meta->add('locale', LanguageService::getLocale($this->lang));
    }
    $meta->add('title', $content->title()->get());
    $meta->add('status', $this->model->status());
    $meta->add('sort', (int) $this->model->num());
    $meta->add('modified',  date('c', $this->model->modified()));
    $meta->add('blueprint', (string) $this->model->intendedTemplate());
    $meta->add('home', $this->model->isHomePage());

    $res = new Collection();
    $res->add('meta', $meta);
    $res->add('link', LinkService::getPage($this->getUrl($this->lang)));
    if ($this->add_translations && LanguageService::isMultilang()) {
      $translations = $res->add('translations');
      foreach(LanguageService::getAll() as $lang => $data) {
        $lang = $translations->add($lang);
        $lang->add('type', 'url');
        $lang->add('link', LinkService::getPage($this->getUrl($this->lang)));
        $lang->add('value', $data->node('name')->get());

        
      }
    }
    return $res;
  }

  /** */
  protected function getValue () {}

  /** */
  private function getUrl ($lang) : string
  {
    $langSlug = LanguageService::getSlug($lang);
    return '/' . ltrim($langSlug . '/' . $this->model->uri($lang), '/');
  }

  /** */
  private function getParentUrl ($lang) : string
  {
    $langSlug = LanguageService::getSlug($lang);
    $parent = $this->model->parent();
    if ($parent) {
      return '/' . ltrim($langSlug . '/' . $parent->uri($lang), '/');
    }
    return '/'; // or false?
  }

  /** */
  private function getSlug ($lang)
  {
    return $this->model->slug($lang);
  }
}