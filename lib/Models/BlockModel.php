<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;

/**
 * Default model for Kirby's blocks
 */
class BlockModel extends BaseModel
{
  /**
   * Nodename for fields.
   */
  protected $valueNodeName = 'fields';

  /**
   * Marker if this model has child fields.
   * 
   * @var bool
   */
  protected $hasChildFields = true;

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $res->add('block', $this->model->type());
    return $res;
  }

  /**
   * Get type of this model as it's returned in response.
   */
  protected function getType(): string
  {
    return 'block';
  }

  /**
   * Get the value of model.
   */
  protected function getValue (): Collection|null
  {
    return $this->fields;
  }
}
