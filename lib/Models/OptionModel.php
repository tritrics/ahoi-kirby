<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: radio, select, toggles
 */
class OptionModel extends Model
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
    return TypeHelper::auto($this->model->value());
  }
}