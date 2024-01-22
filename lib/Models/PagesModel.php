<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Models\PageModel;
use Tritrics\AflevereApi\v1\Services\BlueprintService;

/**
 * Model for Kirby's fields: pages
 */
class PagesModel extends Model
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
  protected function getProperties()
  {
    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('multiple', $this->isMultiple());
    $meta->add('count', $this->model->toPages()->count());
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return Collection
   */
  protected function getValue ()
  {
    $res = new Collection();
    foreach ($this->model->toPages() as $page) {
      if ($page->isDraft()) {
        continue;
      }
      $blueprint = BlueprintService::getBlueprint($page);
      $model = new PageModel($page, $blueprint, $this->lang);
      $res->push($model);
    }
    return $res;
  }
}