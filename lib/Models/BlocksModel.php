<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;

use Tritrics\Ahoi\v1\Factories\BlockFactory;

/**
 * Model for Kirby's fields: blocks
 */
class BlocksModel extends BaseModel
{
  /**
   * Nodename for blocks.
   */
  protected $valueNodeName = 'entries';

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $meta = $res->add('collection');
    $meta->add('count', $this->model->toBlocks()->count());
    return $res;
  }
  
  /**
   * Get the value of model.
   */
  protected function getValue (): Collection
  {
    $res = new Collection();
    foreach ($this->model->toBlocks() as $block) {
      $type = strtolower($block->type());
      $blueprint = $this->blueprint->node('blocks', $type);
      if ($blueprint->has('fields')) {
        $model = BlockFactory::create($type, $block, $blueprint, $this->lang);
        $res->push($model);
      }
    }
    return $res;
  }
}
