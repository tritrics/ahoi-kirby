<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\UrlHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\LinkHelper;

/**
 * Model for Kirby's file object
 */
class FileModel extends BaseModel
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
    $res = new Collection();
    
    // empty model, for empty none-multiple-collections
    if (!$this->model) {
      return $res;
    }

    $parts = UrlHelper::parse($this->model->url());
    $meta = $res->add('meta');
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
