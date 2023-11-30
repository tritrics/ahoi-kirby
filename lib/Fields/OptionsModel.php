<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Services\GlobalService;

/** */
class OptionsModel extends Model
{
  /** */
  protected function getValue () : Collection
  {
    $values = $this->splitSelectedOptions($this->model->value());
    $addLabel = $this->blueprint->node('api', 'labels')->is(true);

    /**
     * OptionsModel can't be used her, because there is no blueprint
     * representation of a single option. So we create a pseudo-field 
     * with type=option here.
     */
    $res = new Collection();
    foreach($values as $key => $value) {
      $option = new Collection();
      $option->add('type', 'option');
      if ($addLabel) {
        $option->add('label', $this->getLabel($value));
      }
      $option->add('value', $value);
      $res->add($key, $option);
    }
    return $res;
  }

  /**
   * split and trim
   */
  private function splitSelectedOptions ($value)
  {
    return array_map(function ($option) {
      return GlobalService::typecast($option, true);
    }, explode(',', $value));
  }
}