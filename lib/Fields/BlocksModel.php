<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\ApiService;

/** */
class BlocksModel extends Model
{
  /** */
  private $classMap = [
    'heading' => '\Blocks\HeadingModel',
  ];

  /** */
  protected function getValue () : Collection
  {
    $res = new Collection();
    foreach ($this->model->toBlocks() as $block) {
      $type = strtolower($block->type());
      if (isset($this->classMap[$type])) {
        $blockClass = ApiService::$namespace . $this->classMap[$type];
      } else {
        $blockClass = ApiService::$namespace . '\Blocks\DefaultModel';
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
