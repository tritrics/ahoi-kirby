<?php

namespace Tritrics\Tric\v1\Models;

use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Helper\FieldHelper;

/**
 * Model for Kirby's fields: structure
 */
class StructureModel extends BaseModel
{
  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue (): Collection
  {
    $res = new Collection();
    foreach ($this->model->toStructure() as $entry) {
      $row = new Collection();
      FieldHelper::addFields(
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