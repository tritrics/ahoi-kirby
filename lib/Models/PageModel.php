<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\LanguageService;
use Tritrics\AflevereApi\v1\Services\LinkService;


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
    $meta->add('slug',$this->model->slug($this->lang));
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
      foreach(LanguageService::list() as $code => $data) {
        $lang = $translations->add($code);
        $lang->add('type', 'url');
        $lang->add('link', LinkService::getPage($this->getUrl($code)));
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
    return '/' . trim($langSlug . '/' . $this->model->uri($lang), '/');
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
}