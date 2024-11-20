<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\BaseModel;

/**
 * Model for Kirby's fields: number, range
 */
class NumberModel extends BaseModel
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
    $this->add('type', 'number');
    $this->add('value', (float) $this->model->value());
  }
}
