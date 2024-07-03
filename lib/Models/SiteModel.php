<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\UrlHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's site object
 */
class SiteModel extends BaseModel
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
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $meta = $res->add('meta');

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
