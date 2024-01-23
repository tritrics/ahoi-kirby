<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Data\Collection;

/**
 * Default model for Kirby's blocks
 */
class BlockDefaultModel extends Model
{
  /**
   * Marker if this model has child fields.
   * 
   * @var Boolean
   */
  protected $hasChildFields = true;

  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   * 
   * @return String 
   */
  protected function getType ()
  {
    return 'block';
  }

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
  protected function getProperties()
  {
    $res = new Collection();
    $res->add('block', $this->model->type());
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return String|number|Boolean
   */
  protected function getValue ()
  {
    return $this->fields;
  }
}
