<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;

/**
 * Model for Kirby's fields: toggle
 *
 * @package   AflevereAPI Models
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 */
class BooleanModel extends Model
{
  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return bool
   */
  protected function getValue ()
  {
    return (float) $this->model->isTrue(); // return 0 or 1 as number
  }
}