<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Models\NodeModel;
use Tritrics\AflevereApi\v1\Services\BlueprintService;

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
      $model = new NodeModel($page, $blueprint, $this->lang);
      $res->push($model);
    }
    return $res;
  }
}