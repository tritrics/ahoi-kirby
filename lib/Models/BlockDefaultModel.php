<?php

namespace Tritrics\AflevereApi\v1\Blocks;

use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Data\Collection;

/**
 * Default model for Kirby's blocks
 *
 * @package   AflevereAPI Models
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 */
class BlockDefaultModel extends Model
{
  /**
   * Marker if this model has child fields.
   * 
   * @var true
   */
  protected $hasChildFields = true;

  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   * 
   * @return string 
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
   * @return string|number|bool
   */
  protected function getValue ()
  {
    return $this->fields;
  }
}
