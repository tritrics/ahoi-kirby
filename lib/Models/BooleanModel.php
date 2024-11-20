<?php

namespace Tritrics\Ahoi\v1\Models;

/**
 * Model for Kirby's fields: toggle
 */
class BooleanModel extends BaseModel
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
    $this->add('type', 'boolean');
    $this->add('value', (float) $this->model->isTrue());
  }
}