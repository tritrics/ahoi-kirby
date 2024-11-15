<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\Page;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's fields: pages
 */
class PagesModel extends BaseModel
{
  /**
   * Nodename for pages.
   */
  protected $valueNodeName = 'entries';

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
    foreach ($this->model->toPages() as $page) {
      if ($page->isDraft()) {
        continue;
      }
      $blueprint = BlueprintHelper::get($page);
      $model = $this->createEntry($page, $blueprint, $this->lang, $this->addFields);
      $res->push($model);
    }

    // return only one element (no collection) if multiple is false
    return $res;
  }
}