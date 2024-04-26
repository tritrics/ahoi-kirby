<?php

namespace Tritrics\Tric\v1\Models;

use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's fields: files
 */
class FilesModel extends BaseModel
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
    $meta->add('count', $this->model->toFiles()->count());
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue (): Collection
  {
    $res = new Collection();
    foreach ($this->model->toFiles() as $file) {
      $blueprint = BlueprintHelper::getBlueprint($file);
      $model = new FileModel($file, $blueprint, $this->lang);
      $res->push($model);
    }
    return $res;
  }
}