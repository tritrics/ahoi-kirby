<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Model;

/**
 * Model for Kirby's fields: number, range
 *
 * @package   AflevereAPI Models
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 */
class NumberModel extends Model
{
  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return number
   */
  protected function getValue ()
  {
    return (float) $this->model->value();
  }
}
