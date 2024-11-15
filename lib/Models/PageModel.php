<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\UrlHelper;
use Tritrics\Ahoi\v1\Models\LanguageModel;

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
  protected $hasFields = true;

  /**
   * Nodename for fields.
   */
  protected $valueNodeName = 'fields';

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties (): Collection
  {
    $res = new Collection();

    // empty model, for empty none-multiple-collections
    if (!$this->model) {
      return $res;
    }

    // global values
    $meta = $res->add('meta');
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

    // languages
    if (ConfigHelper::isMultilang() && $this->addDetails) {
      $languages = $res->add('languages');
      foreach (LanguagesHelper::getLang() as $lang) {
        $languages->push(
          new LanguageModel($this->model, null, $lang)
        );
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