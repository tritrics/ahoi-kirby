<?php

namespace Tritrics\Api\Fields;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Models\FileModel;
use Tritrics\Api\Services\BlueprintService;

/** */
class FilesModel extends Model
{
  /** */
  protected function getValue ()
  {
    $res = new Collection();
    foreach ($this->model->toFiles() as $file) {
      $blueprint = BlueprintService::getBlueprint($file);
      $model = new FileModel($file, $blueprint, $this->lang);
      $res->push($model);
    }
    return $res;
  }
}