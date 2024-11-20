<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\File;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Data\EntriesModel;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;

/**
 * Model for Kirby's fields: files
 */
class FilesModel extends EntriesModel
{
  /**
   */
  public function __construct() {
    parent::__construct(...func_get_args());
    $this->setEntries(KirbyHelper::filterCollection($this->model->toFiles()));
    $this->setData();
  }

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
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'files');
    
    // meta
    $meta = $this->add('collection');
    $meta->add('count', $this->entries->count());

    // entries
    $entries = $this->add('entries');
    foreach ($this->entries as $file) {
      $blueprint = BlueprintHelper::get($file);
      $model = $this->createEntry($file, $blueprint, $this->lang, $this->addFields);
      $entries->push($model);
    }
  }
}