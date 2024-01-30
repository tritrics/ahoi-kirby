<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: checkboxes, multiselect, tags
 */
class OptionsModel extends Model
{
  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   */
  protected function getValue () : Collection
  {
    $values = $this->splitSelectedOptions($this->model->value());
    $addLabel = $this->blueprint->node('api', 'labels')->is(true);

    // OptionsModel can't be used her, because there is no blueprint
    // representation of a single option. So we create a pseudo-field 
    // with type=option here.
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
   * Helper to split and trim the defined options.
   */
  private function splitSelectedOptions (string|int|float $value): array
  {
    return array_map(function ($option) {
      return TypeHelper::auto($option, true);
    }, explode(',', $value));
  }
}