<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\Page;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;
use Tritrics\Ahoi\v1\Helper\LinkHelper;

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
   * Method called by setModelData()
   */
  protected function getProperties (): Collection
  {
    $res = new Collection();
    $content = $this->model->content($this->lang);
    $attr = LinkHelper::get($this->model, null, false, $this->lang, 'page');

    $meta = $res->add('meta');
    if ($this->model instanceof Page) {
      $meta->add('id', $this->model->id());
      $meta->add('slug', $this->model->slug($this->lang));
      $meta->add('href', $attr['href']);
      $meta->add('parent', KirbyHelper::getParentUrl($this->model, $this->lang));
      $meta->add('blueprint', (string) $this->model->intendedTemplate());
    } else {
      $meta->add('host', $this->model->url($this->lang));
      $meta->add('blueprint', 'site');
    }
    $meta->add('title', $content->title()->value());
    if ($this->model instanceof Page) {
      $meta->add('status', $this->model->status());
      $meta->add('sort', (int) $this->model->num());
      $meta->add('home', $this->model->isHomePage());
    }
    if ($this->lang !== null) {
      $meta->add('lang', $this->lang);
    }
    $meta->add('modified',  date('c', $this->model->modified()));

    if (ConfigHelper::isMultilang()) {
      $node = new Collection();
      foreach (LanguagesHelper::list() as $code => $data) {
        $node->add($code, KirbyHelper::getNodeUrl($this->model, $code));
      }
    } else {
      $node = KirbyHelper::getNodeUrl($this->model, $this->lang);
    }
    $meta->add('node', $node);
    
    if ($this->blueprint->has('api', 'meta')) {
      $api = $meta->add('api');
      foreach ($this->blueprint->node('api', 'meta')->get() as $key => $value) {
        $api->add($key, $value);
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