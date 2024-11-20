<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;

/**
 * Model for Kirby's site object
 */
class SiteModel extends BaseFieldsModel
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
    $this->add('type', 'site');

    $meta = $this->add('meta');

    // global values
    $meta->add('blueprint', 'site');
    $meta->add('modified',  date('c', $this->model->modified()));
    $meta->add('node', ConfigHelper::isMultilang() ? '/' . $this->lang : '');
    // avoid redundand data, take home-slug from infoService and current langslug
    // $meta->add('home', UrlHelper::getNode($this->model->homePage(), $this->lang));
    $meta->add('title', $this->model->content($this->lang)->title()->value());

    // language specific
    if (ConfigHelper::isMultilang()) {
      $meta->add('lang', $this->lang);
    }

    // languages
    if (ConfigHelper::isMultilang()) {
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
