<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;
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
    $parts = UrlHelper::parse($this->model->url());
    $page = $this->model->parent($this->lang);

    $meta = new Collection();
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

    // adding translations
    if (ConfigHelper::isMultilang()) {
      if ($this->addDetails) {
        $translations = new Collection();
        foreach (LanguagesHelper::getCodes() as $code) {
          $attr = LinkHelper::get($this->model, null, false, $code, 'file');
          $translations->push([
            'lang' => $code,
            'href' => $attr['href'],
            'node' => '/' . ltrim($code . '/' . $page->uri($code), '/') . '/' . $this->model->filename()
          ]);
        }
        $meta->add('translations', $translations);
      }
    }

    $res = new Collection();
    $res->add('meta', $meta);
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
