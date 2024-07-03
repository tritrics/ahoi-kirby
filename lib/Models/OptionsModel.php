<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Model for Kirby's fields: checkboxes, multiselect, tags
 */
class OptionsModel extends BaseModel
{
  /**
   * Nodename for options.
   */
  protected $valueNodeName = 'entries';

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $options = TypeHelper::optionsToArray($this->model->value());
    $res = new Collection();
    $meta = $res->add('collection');
    $meta->add('count', count($options));
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue () : Collection
  {
    $addLabel = $this->blueprint->node('api', 'labels')->is(true);

    // OptionsModel can't be used her, because there is no blueprint
    // representation of a single option. So we create a pseudo-field 
    // with type=option here.
    $res = new Collection();
    foreach(TypeHelper::optionsToArray($this->model->value()) as $key => $value) {
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
}