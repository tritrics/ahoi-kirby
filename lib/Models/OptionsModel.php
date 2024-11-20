<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: checkboxes, multiselect, tags
 */
class OptionsModel extends BaseEntriesModel
{
  /**
   */
  public function __construct() {
    parent::__construct(...func_get_args());
    $this->setEntries(TypeHelper::optionsToArray($this->model->value()));
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'options');

    // meta
    $meta = $this->add('collection');
    $meta->add('count', count($this->entries));

    // entries
    // OptionsModel can't be used her, because there is no blueprint
    // representation of a single option. So we create a pseudo-field 
    // with type=option here.
    $addLabel = $this->blueprint->node('api', 'labels')->is(true);
    $entries = $this->add('entries');
    foreach ($this->entries as $key => $value) {
      $option = new Collection();
      $option->add('type', 'option');
      if ($addLabel) {
        $option->add('label', $this->getLabel($value));
      }
      $option->add('value', $value);
      $entries->add($key, $option);
    }
  }
}