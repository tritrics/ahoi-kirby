<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\GlobalService;

/** */
class SelectModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    $res->add('label', $this->getLabel());
    return $res;
  }

  /** */
  protected function getValue ()
  {
    return GlobalService::typecast($this->model->value());
  }

  /**
   * Get the corresponding label for the selected option
   */
  protected function getLabel ()
  {
    $value = $this->model->value();
    $options = $this->blueprint->node('options');
    if ($options instanceof Collection && $options->count() > 0) {
      $options = $options->get(false);
      $type = $this->checkOpionsType($options);
      if ($type === 'IS_STRING') {
        return isset($options[$value]) ? $options[$value] : $value;
      }
      if ($type === 'IS_KEY_VALUE') {
        foreach ($options as $entry) {
          if ($entry['value'] == $value) {
            return $entry['text'];
          }
        }
      }
    }
    return '';
  }
}