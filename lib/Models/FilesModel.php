<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Models\FileModel;
use Tritrics\AflevereApi\v1\Services\BlueprintService;

/** */
class FilesModel extends Model
{
  protected function getProperties()
  {
    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('multiple', $this->isMultiple());
    $meta->add('count', $this->model->toFiles()->count());
    return $res;
  }

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