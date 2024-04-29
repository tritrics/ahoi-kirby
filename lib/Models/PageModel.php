<?php

namespace Tritrics\Tric\v1\Models;

use Kirby\Cms\Page;
use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Helper\LanguagesHelper;
use Tritrics\Tric\v1\Helper\LinkHelper;
use Tritrics\Tric\v1\Helper\ConfigHelper;
use Tritrics\Tric\v1\Helper\KirbyHelper;

/**
 * Model for Kirby's page object
 */
class PageModel extends BaseModel
{
  /**
   * Marker if this model has child fields.
   * 
   * @var bool
   */
  protected $hasChildFields = true;

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties (): Collection
  {
    $res = new Collection();
    $content = $this->model->content($this->lang);
    $attr = LinkHelper::get($this->model, null, false, $this->lang, 'page');

    $meta = $res->add('meta');
    if ($this->model instanceof Page) {
      $meta->add('id', $this->model->id());
      $meta->add('slug', $this->model->slug($this->lang));
      $meta->add('parent', KirbyHelper::getParentUrl($this->model, $this->lang));
      $meta->add('href', $attr['href']);
      $meta->add('blueprint', (string) $this->model->intendedTemplate());
    } else {
      $meta->add('host', $this->model->url($this->lang));
      $meta->add('href', $attr['href']);
      $meta->add('blueprint', 'site');
    }
    $meta->add('title', $content->title()->value());
    if ($this->model instanceof Page) {
      $meta->add('status', $this->model->status());
      $meta->add('sort', (int) $this->model->num());
      $meta->add('home', $this->model->isHomePage());
    }
    if ($this->lang !== null) {
      $meta->add('lang', $this->lang);
      $meta->add('locale', LanguagesHelper::getLocale($this->lang));
    }
    $meta->add('modified',  date('c', $this->model->modified()));
    
    if ($this->blueprint->has('api', 'meta')) {
      foreach ($this->blueprint->node('api', 'meta')->get() as $key => $value) {
        if (!$meta->has($key)) {
          $meta->add($key, $value);
        }
      }
    }

    if (ConfigHelper::isMultilang()) {
      $translations = $res->add('translations');
      foreach(LanguagesHelper::list() as $code => $data) {
        $attr = LinkHelper::get($this->model, null, false, $code, 'page');
        $translations->add($code, $attr['href']);
      }
    }
    return $res;
  }
}