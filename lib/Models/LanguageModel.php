<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\Site;
use Kirby\Cms\Page;
use Kirby\Cms\File;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\LinkHelper;
use Tritrics\Ahoi\v1\Helper\UrlHelper;

/**
 * Model for Kirby's language object
 */
class LanguageModel extends BaseModel
{
  /**
   * Nodename for fields.
   */
  protected $valueNodeName = 'fields';

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $language = LanguagesHelper::get($this->lang);
    $lang = trim(strtolower($language->code()));

    $res = new Collection();
    $meta = $res->add('meta');
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
      $meta->add('node',LanguagesHelper::getLangSlug($lang));
    }
    
    // Language
    else {
      $meta->add('node', LanguagesHelper::getLangSlug($lang));
      $meta->add('origin', LanguagesHelper::getOrigin($lang));
      $meta->add('locale', LanguagesHelper::getLocale($lang));
      $meta->add('direction', $language->direction());
    }
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue(): Collection|null
  {
    // Value for language (=translations) are added if a single language is requested.
    if ($this->addDetails) {
      $fields = new Collection();
      $separator = ConfigHelper::get('field_name_separator', '');
      $language = LanguagesHelper::get($this->lang);
      $languageDefault = LanguagesHelper::getDefault();
      $translations = $language->translations();
      foreach ($languageDefault->translations() as $key => $foo) {
        $value = isset($translations[$key]) ? $translations[$key] : '';
        if ($separator) {
          $key = explode($separator, $key);
        }
        $fields->add($key, [
          'type' => 'string',
          'value' => $value
        ]);
      }
      return $fields;
    }
    return null;
  }
}
