<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: radio, select, toggles
 */
class OptionModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties (): Collection
  {
    $res = new Collection();
    if ($this->blueprint->node('api', 'labels')->is(true)) {
      $res->add('label', $this->getLabel($this->model->value()));
    }
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue (): string|int|float
  {
    return TypeHelper::toChar($this->model->value());
  }
}