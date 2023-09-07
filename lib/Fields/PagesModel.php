<?php

namespace Tritrics\Api\Fields;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Models\PageModel;
use Tritrics\Api\Services\BlueprintService;

/** */
class PagesModel extends Model
{
  /** */
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