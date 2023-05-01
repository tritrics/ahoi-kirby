<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;

/** */
class BlocksModel extends Model
{
  /** */
  private $classMap = [
    'heading' => 'Tritrics\Api\Models\BlockHeadingModel',
  ];

  /** */
  protected function getValue () : Collection
  {
    $res = new Collection();
    foreach ($this->model->toBlocks() as $block) {
      $type = strtolower($block->type());
      if (isset($this->classMap[$type])) {
        $blockClass = $this->classMap[$type];
      } else {
        $blockClass = 'Tritrics\Api\Models\BlockDefaultModel';
      }
      $blueprint = $this->blueprint->node('blocks', $type);
      if ($blueprint->has('fields')) {
        $model = new $blockClass($block, $blueprint, $this->lang);
        $res->push($model);
      }
    }
    return $res;
  }
}
