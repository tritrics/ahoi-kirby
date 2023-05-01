<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\LanguageService;


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

    $res = new Collection();
    $res->add('id', $this->model->id());
    $res->add('parent', $this->getParentUri($this->lang));
    $res->add('slug',  $this->getSlug($this->lang));
    if ($this->lang !== null) {
      $res->add('lang', $this->lang);
    }
    $res->add('title', $content->title()->get());
    $res->add('status', $this->model->status());
    $res->add('sort', (int) $this->model->num());
    $res->add('modified',  date('c', $this->model->modified()));
    $res->add('blueprint', (string) $this->model->intendedTemplate());
    $res->add('home', $this->model->isHomePage());

    $link = $res->add('link');
    $link->add('type', 'intern');
    $link->add('uri', $this->getUri($this->lang));
    $link->add('title', $content->title()->get());

    if ($this->add_translations && LanguageService::isMultilang()) {
      $translations = $res->add('translations');
      foreach(LanguageService::getAll() as $lang => $data) {
        $translation = $translations->add($lang);
        $translation->add('type', 'intern');
        $translation->add('uri', $this->getUri($lang));
        $translation->add('title', $data->node('name')->get());
      }
    }
    return $res;
  }

  /** */
  protected function getValue () {}

  /** */
  private function getUri ($lang) : string
  {
    $langSlug = LanguageService::getSlug($lang);
    return '/' . ltrim($langSlug . '/' . $this->model->uri($lang), '/');
  }

  /** */
  private function getParentUri ($lang) : string
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