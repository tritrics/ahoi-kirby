<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: radio, select, toggles
 */
class OptionModel extends BaseModel
{
  /**
   */
  public function __construct()
  {
    parent::__construct(...func_get_args());
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'option');
    if ($this->blueprint->node('api', 'labels')->is(true)) {
      $this->add('label', $this->getLabel($this->model->value()));
    }
    $this->add('value', TypeHelper::toChar($this->model->value()));
  }
}