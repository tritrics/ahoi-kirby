<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's fields: pages
 */
class PagesModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('multiple', $this->isMultiple());
    $meta->add('count', $this->model->toPages()->count());
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue (): Collection
  {
    $res = new Collection();
    foreach ($this->model->toPages() as $page) {
      if ($page->isDraft()) {
        continue;
      }
      $blueprint = BlueprintHelper::getBlueprint($page);
      $model = new PageModel($page, $blueprint, $this->lang);
      $res->push($model);
    }
    return $res;
  }
}