<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's fields: pages
 */
class PagesModel extends BaseModel
{
  /**
   * Nodename for pages.
   */
  protected $valueNodeName = 'entries';
  
  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $meta = $res->add('collection');
    $meta->add('count', $this->model->toPages()->count());
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue (): Collection
  {
    $addFields = []; // no fields added on default, must be explizit set.
    if ($this->blueprint->node('api')->has('fields')) {
      $addFields = $this->blueprint->node('api')->node('fields')->get();
    }
    $res = new Collection();
    foreach ($this->model->toPages() as $page) {
      if ($page->isDraft()) {
        continue;
      }
      $blueprint = BlueprintHelper::get($page);
      $model = new PageModel($page, $blueprint, $this->lang, $addFields);
      $res->push($model);
    }
    return $res;
  }
}