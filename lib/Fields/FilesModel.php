<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Models\FileModel;
use Tritrics\AflevereApi\v1\Services\BlueprintService;

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