<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\FieldHelper;

/**
 * Model for Kirby's fields: structure
 */
class StructureModel extends BaseModel
{
  /**
   * Nodename for rows.
   */
  protected $valueNodeName = 'entries';

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $meta = $res->add('collection');
    $meta->add('count', $this->model->toStructure()->count());
    return $res;
  }

  /**
   * Get the value of model.
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