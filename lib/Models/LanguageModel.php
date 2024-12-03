<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\Site;
use Kirby\Cms\Page;
use Kirby\Cms\File;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Data\FieldsModel;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\UrlHelper;

/**
 * Model for Kirby's language object
 */
class LanguageModel extends FieldsModel
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
    $this->add('type', 'language');

    // meta
    $language = LanguagesHelper::get($this->lang);
    $lang = trim(strtolower($language->code()));
    $meta = $this->add('meta');
    $meta->add('lang', $lang);
    $meta->add('title', $language->name());
    $meta->add('default', $language->isDefault());

    // different properties for different models
    // Page
    if ($this->model instanceof Page) {
      $meta->add('node', UrlHelper::getNode($this->model, $lang));

      // href can be different from node, because of language setting "url".
      // node is always starting with langcode, where as the href can f.ex. have
      // different urls to distinguish the languages.
      $meta->add('href', UrlHelper::getPath($this->model->url($lang)));
    }

    // File
    else if ($this->model instanceof File) {
      $page = $this->model->parent($this->lang);
      $meta->add('node', '/' . ltrim($lang . '/' . $page->uri($lang), '/') . '/' . $this->model->filename());

      // does href make sense?
      //$attr = LinkHelper::get($this->model, null, false, $lang, 'file');
      //$meta->add('href', $attr['href']);
    }

    // Site
    else if ($this->model instanceof Site) {
      $meta->add('node', LanguagesHelper::getLangSlug($lang));
    }

    // Language
    else {
      $meta->add('node', LanguagesHelper::getLangSlug($lang));
      $meta->add('origin', LanguagesHelper::getOrigin($lang));
      $meta->add('locale', LanguagesHelper::getLocale($lang));
      $meta->add('direction', $language->direction());
    }

    // fields
    if ($this->fields->count() > 0) {
      $this->add('fields', $this->fields);
    }
  }

  /**
   * Overwrite setFields() function, because here fields are the terms.
   */
  protected function setFields(): void
  {
    $this->fields = new Collection();

    if (count($this->addFields) > 0) {
      $separator = ConfigHelper::get('field_name_separator', '');
      $language = LanguagesHelper::get($this->lang);
      $languageDefault = LanguagesHelper::getDefault();
      $translations = $language->translations();
      foreach ($languageDefault->translations() as $key => $foo) {

        // $addFields can be: [ '*', 'foo', 'foo_bar', 'foo_*', 'foo_bar_*' ]
        if (!in_array('*', $this->addFields) && !in_array($key, $this->addFields)) {
          continue;
        }
        $value = isset($translations[$key]) ? $translations[$key] : '';
        if ($separator) {
          $key = explode($separator, $key);
        }
        $this->fields->add($key, [
          'type' => 'string',
          'value' => (string) $value
        ]);
      }
    }
  }
}
