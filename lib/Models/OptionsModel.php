<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Data\EntriesModel;
use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: checkboxes, multiselect, tags
 */
class OptionsModel extends EntriesModel
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
    $entries = $this->add('entries');
    foreach ($this->entries as $key => $value) {
      $option = new Collection();
      $option->add('type', 'option');
      // $option->add('label', $this->getLabel($value)); @see OptionModel
      $option->add('value', $value);
      $entries->add($key, $option);
    }
  }
}