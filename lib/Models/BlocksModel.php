<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;

use Tritrics\AflevereApi\v1\Factories\BlockFactory;

/**
 * Model for Kirby's fields: blocks
 */
class BlocksModel extends BaseModel
{
  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
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
