<?php

namespace Tritrics\Ahoi\v1\Models;

/**
 * Model for Kirby's fields: toggle
 */
class BooleanModel extends BaseModel
{
  /**
   * Get the value of model.
   */
  protected function getValue (): int
  {
    return (float) $this->model->isTrue(); // return 0 or 1 as number
  }
}