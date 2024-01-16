<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\FieldService;

/**
 * Model for Kirby's fields: structure
 *
 * @package   AflevereAPI Models
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 */
class StructureModel extends Model
{
  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return Collection 
   */
  protected function getValue ()
  {
    $res = new Collection();
    foreach ($this->model->toStructure() as $entry) {
      $row = new Collection();
      FieldService::addFields(
        $row,
        $entry->content($this->lang)->fields(),
        $this->blueprint->node('fields'),
        $this->lang
      );
      $res->push($row);
    }
    return $res;
  }
}