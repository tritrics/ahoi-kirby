<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\File;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;

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
   * Create a child entry instance
   */
  public function createEntry(
    File $model = null,
    Collection $blueprint = null,
    string $lang = null,
    array $addFields = []
  ): Collection {
    return new FileModel($model, $blueprint, $lang, $addFields);
  }

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
    $res = new Collection();
    $children = KirbyHelper::filterCollection($this->model->toFiles());
    foreach ($children as $file) {
      $blueprint = BlueprintHelper::get($file);
      $model = $this->createEntry($file, $blueprint, $this->lang, $this->addFields);
      $res->push($model);
    }
    return $res;
  }
}