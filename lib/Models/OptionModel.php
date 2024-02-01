<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: radio, select, toggles
 */
class OptionModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
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
   * Get the value of model as it's returned in response.
   * Mandatory method.
   */
  protected function getValue (): string|int|float
  {
    return TypeHelper::toChar($this->model->value());
  }
}