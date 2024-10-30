<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\UrlHelper;

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
   * Nodename for fields.
   */
  protected $valueNodeName = 'fields';

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties (): Collection
  {
    // empty model, for empty none-multiple-collections
    if (!$this->model) {
      $res = new Collection();
      $res->add('meta', []);
      return $res;
    }
    
    $res = new Collection();
    $meta = $res->add('meta');

    // global values
    $meta->add('blueprint', (string) $this->model->intendedTemplate());
    $meta->add('status', $this->model->status());
    if ($this->model->status() === 'listed') {
      $meta->add('sort', (int) $this->model->num());
    }
    $meta->add('home', $this->model->isHomePage());
    $meta->add('modified',  date('c', $this->model->modified()));
    $meta->add('slug', $this->model->slug($this->lang));
    $meta->add('href', UrlHelper::getPath($this->model->url($this->lang)));
    $meta->add('node', UrlHelper::getNode($this->model, $this->lang));
    $meta->add('title', $this->model->content($this->lang)->title()->value());

    // language specific
    if (ConfigHelper::isMultilang()) {
      $meta->add('lang', $this->lang);
    }

    // optional api meta values
    if ($this->blueprint->has('api', 'meta')) {
      $api = new Collection();
      foreach ($this->blueprint->node('api', 'meta')->get() as $key => $value) {
        $api->add($key, $value);
      }
      if ($api->count() > 0) {
        $meta->add('api', $api);
      }
    }

    // translations
    if (ConfigHelper::isMultilang() && $this->addDetails) {
      $translations = $res->add('translations');
      foreach (LanguagesHelper::getAll() as $language) {
        $code = $language->code();
        $translation = new Collection();
        $translation->add('lang', $code);
        $translation->add('slug', $this->model->slug($code));
        $translation->add('href', UrlHelper::getPath($this->model->url($code)));
        $translation->add('node', UrlHelper::getNode($this->model, $code));
        $translation->add('title', $language->name());
        $translations->push($translation);
      }
    }
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue(): Collection|null
  {
    return $this->fields;
  }
}