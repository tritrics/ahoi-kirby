<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\Page;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;

/**
 * Model for Kirby's fields: pages
 */
class PagesModel extends BaseEntriesModel
{
  /**
   * Constructor with additional initialization.
   */
  public function __construct() {
    parent::__construct(...func_get_args());
    $this->setEntries(KirbyHelper::filterCollection($this->model->toPages(), ['status' => 'published ']));
    $this->setData();
  }

  /**
   * Create a child entry instance
   */
  public function createEntry(
    Page $model = null,
    Collection $blueprint = null,
    string $lang = null,
    array $addFields = []
  ): Collection {
    return new PageModel($model, $blueprint, $lang, $addFields);
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'pages');

    // meta
    $meta = $this->add('collection');
    $meta->add('count', $this->entries->count());

    // entries
    $entries = $this->add('entries');
    foreach ($this->entries as $page) {
      $blueprint = BlueprintHelper::get($page);
      $model = $this->createEntry($page, $blueprint, $this->lang, $this->addFields);
      $entries->push($model);
    }
  }
}