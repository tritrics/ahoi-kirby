<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's fields: files
 */
class FilesModel extends BaseModel
{
  /**
   * Nodename for files.
   */
  protected $valueNodeName = 'entries';

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $meta = $res->add('collection');
    $meta->add('count', $this->model->toFiles()->count());
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
    foreach ($this->model->toFiles() as $file) {
      $blueprint = BlueprintHelper::get($file);
      $model = new FileModel($file, $blueprint, $this->lang, $addFields);
      $res->push($model);
    }
    return $res;
  }
}