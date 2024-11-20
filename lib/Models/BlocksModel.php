<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Models\BlockModel;

/**
 * Model for Kirby's fields: blocks
 */
class BlocksModel extends BaseEntriesModel
{
  /**
   * Constructor with additional initialization.
   */
  public function __construct() {
    parent::__construct(...func_get_args());
    $this->setEntries($this->model->toBlocks());
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'blocks');

    // meta
    $meta = $this->add('collection');
    $meta->add('count', $this->entries->count());

    // entries
    $entries = $this->add('entries');
    foreach ($this->entries as $block) {
      $type = strtolower($block->type());
      $blueprint = $this->blueprint->node('blocks', $type);
      if ($blueprint->has('fields')) {
        $model = new BlockModel($block, $blueprint, $this->lang, [ '*' ]);
        $entries->push($model);
      }
    }
  }
}
