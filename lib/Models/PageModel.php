<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Helper\LanguagesHelper;
use Tritrics\AflevereApi\v1\Helper\LinkHelper;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;

/**
 * Model for Kirby's page object
 */
class PageModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties (): Collection
  {
    $content = $this->model->content($this->lang);

    $res = new Collection();

    $meta = $res->add('meta');
    $meta->add('id', $this->model->id());
    $meta->add('parent', $this->getParentUrl($this->lang));
    $meta->add('slug',$this->model->slug($this->lang));
    if ($this->lang !== null) {
      $meta->add('lang', $this->lang);
      $meta->add('locale', LanguagesHelper::getLocale($this->lang));
    }
    $meta->add('title', $content->title()->get());
    $meta->add('status', $this->model->status());
    $meta->add('sort', (int) $this->model->num());
    $meta->add('modified',  date('c', $this->model->modified()));
    $meta->add('blueprint', (string) $this->model->intendedTemplate());
    $meta->add('home', $this->model->isHomePage());

    if ($this->blueprint->has('api', 'meta')) {
      foreach ($this->blueprint->node('api', 'meta')->get() as $key => $value) {
        if (!$meta->has($key)) {
          $meta->add($key, $value);
        }
      }
    }

    $res->add('link', LinkHelper::getPage(
      LanguagesHelper::getUrl($this->lang, $this->model->uri($this->lang))
    ));

    if ($this->addDetails && ConfigHelper::isMultilang()) {
      $translations = $res->add('translations');
      foreach(LanguagesHelper::list() as $code => $data) {
        $lang = $translations->add($code);
        $lang->add('type', 'url');
        $lang->add('link', LinkHelper::getPage(
          LanguagesHelper::getUrl($code, $this->model->uri($code))
        ));
        $lang->add('value', $data->node('name')->get());
      }
    }
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue (): void {}

  /**
   * Get url of parent model
   */
  private function getParentUrl (?string $lang) : string
  {
    $langSlug = LanguagesHelper::getSlug($lang);
    $parent = $this->model->parent();
    if ($parent) {
      return '/' . ltrim($langSlug . '/' . $parent->uri($lang), '/');
    }
    return '/'; // or false?
  }
}