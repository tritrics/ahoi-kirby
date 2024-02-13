<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;

/**
 * Default model for Kirby's blocks
 */
class BlockDefaultModel extends BaseModel
{
  /**
   * Marker if this model has child fields.
   * 
   * @var bool
   */
  protected $hasChildFields = true;

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $res->add('block', $this->model->type());
    return $res;
  }

  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   */
  protected function getType(): string
  {
    return 'block';
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   */
  protected function getValue (): mixed
  {
    return $this->fields;
  }
}
