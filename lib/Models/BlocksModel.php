<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\ApiService;

/** */
class BlocksModel extends Model
{
  /** */
  private $classMap = [
    'default' => '\Blocks\DefaultModel',
    'heading' => '\Blocks\HeadingModel',
  ];

  /** */
  protected function getValue ()
  {
    $res = new Collection();
    foreach ($this->model->toBlocks() as $block) {
      $type = strtolower($block->type());
      if (isset($this->classMap[$type])) {
        $blockClass = ApiService::$namespace . $this->classMap[$type];
      } else {
        $blockClass = ApiService::$namespace . $this->classMap['default'];
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
