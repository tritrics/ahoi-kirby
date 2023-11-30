<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\FieldService;

/** */
class StructureModel extends Model
{
  /** */
  protected function getValue () : Collection
  {
    $res = new Collection();
    foreach ($this->model->toStructure() as $entry) {
      $row = new Collection();
      FieldService::addFields(
        $row,
        $entry->content($this->lang)->fields(),
        $this->blueprint->node('fields'),
        $this->lang
      );
      $res->push($row);
    }
    return $res;
  }
}