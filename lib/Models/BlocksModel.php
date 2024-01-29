<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Factories\ModelFactory;

/**
 * Model for Kirby's fields: blocks
 */
class BlocksModel extends Model
{
  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return Collection
   */
  protected function getValue ()
  {
    $res = new Collection();
    foreach ($this->model->toBlocks() as $block) {
      $type = strtolower($block->type());
      $blueprint = $this->blueprint->node('blocks', $type);
      if ($blueprint->has('fields')) {
        $model = ModelFactory::createBlock($type, $block, $blueprint, $this->lang);
        $res->push($model);
      }
    }
    return $res;
  }
}
