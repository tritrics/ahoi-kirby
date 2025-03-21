<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Data\EntriesModel;
use Tritrics\Ahoi\v1\Helper\FieldHelper;

/**
 * Model for Kirby's fields: structure
 */
class StructureModel extends EntriesModel
{
  /**
   */
  public function __construct() {
    parent::__construct(...func_get_args());
    $this->addFields = array_merge(['*'], $this->addFields);
    $this->setEntries($this->model->toStructure());
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'structure');

    // meta
    $meta = $this->add('collection');
    $meta->add('count', $this->entries->count());

    // entries
    $entries = $this->add('entries');
    foreach ($this->entries as $entry) {
      $row = new Collection();
      FieldHelper::addFields(
        $row,
        $entry->content($this->lang)->fields(),
        $this->blueprint->node('fields'),
        $this->lang,
        $this->addFields
      );
      $entries->push($row);
    }
  }
}