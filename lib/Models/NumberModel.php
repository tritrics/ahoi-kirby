<?php

namespace Tritrics\Tric\v1\Models;

/**
 * Model for Kirby's fields: number, range
 */
class NumberModel extends BaseModel
{
  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue (): int|float
  {
    return (float) $this->model->value();
  }
}
