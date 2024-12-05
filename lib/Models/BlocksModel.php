<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\EntriesModel;
use Tritrics\Ahoi\v1\Helper\AccessHelper;
use Tritrics\Ahoi\v1\Models\BlockModel;

/**
 * Model for Kirby's fields: blocks
 */
class BlocksModel extends EntriesModel
{
  /**
   * Constructor with additional initialization.
   */
  public function __construct()
  {
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

    // We always add all fields of each block, because if the blocks-field itself is added to
    // the request (otherwise we would not be here), the intention to get the block-fields
    // is obvious. Possible child fields refer to object-, structure-, files- or pages-fields
    // in blocks.
    list($parent, $blocks) = AccessHelper::splitFields($this->addFields);
    $addFields = ['*'];
    if (isset($blocks['*'])) {
      $addFields = array_merge($addFields, $blocks['*']);
    }

    foreach ($this->entries as $block) {
      $type = strtolower($block->type());
      $blueprint = $this->blueprint->node('blocks', $type);
      if ($blueprint->has('fields')) {
        if (isset($blocks[$type])) {
          $addFieldsBlock = array_merge($addFields, $blocks[$type]);
        } else {
          $addFieldsBlock = $addFields;
        }
        $model = new BlockModel($block, $blueprint, $this->lang, $addFieldsBlock);
        $entries->push($model);
      }
    }
  }
}
