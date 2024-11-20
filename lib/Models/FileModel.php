<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Helper\UrlHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;

/**
 * Model for Kirby's file object
 */
class FileModel extends BaseFieldsModel
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
    $this->add('type', 'file');

    // empty model, for empty none-multiple-collections
    if (!$this->model) {
      return;
    }

    $parts = UrlHelper::parse($this->model->url());
    $meta = $this->add('meta');
    $meta->add('host', UrlHelper::buildHost($parts));
    $meta->add('dir', $parts['dirname']);
    $meta->add('file', $parts['basename']);
    $meta->add('name', $parts['filename']);
    $meta->add('ext', $parts['extension']);
    $meta->add('href', $this->model->url());
    $meta->add('node', UrlHelper::getNode($this->model, $this->lang));
    if (ConfigHelper::isMultilang()) {
      $meta->add('lang', $this->lang);
    }
    $meta->add('filetype', $this->model->type());
    $meta->add('blueprint', $this->model->template());
    $meta->add('title', $parts['filename']);
    $meta->add('modified',  date('c', $this->model->modified()));
    if ($this->model->type() === 'image') {
      $meta->add('width', $this->model->width());
      $meta->add('height', $this->model->height());
    }

    // adding languages
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
