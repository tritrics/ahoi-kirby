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
class PageModel extends BaseFieldsModel
{
  /**
   */
  public function __construct()
  {
    parent::__construct(...func_get_args());
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'page');

    // empty model, for empty none-multiple-collections
    if (!$this->model) {
      return;
    }

    // global values
    $meta = $this->add('meta');
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
      $languages = $this->add('languages');
      foreach (LanguagesHelper::getLang() as $lang) {
        $languages->push(
          new LanguageModel($this->model, null, $lang)
        );
      }
    }

    // fields
    if ($this->fields->count() > 0) {
      $this->add('fields', $this->fields);
    }
  }
}