<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\GlobalService;

/** */
class MultiselectModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    $res->add('label', $this->getLabel());
    return $res;
  }

  /** */
  protected function getValue () : Collection
  {
    $res = new Collection();
    $res->set($this->splitSelectedOptions($this->model->value()));
    return $res;
  }
  
  /**
   * Get a list similar to value() with the labels of the selected options
   */
  protected function getLabel ()
  {
    $res = new Collection();
    $values =  $this->splitSelectedOptions($this->model->value());
    $options = $this->blueprint->node('options');
    if ($options instanceof Collection && $options->count() > 0) {
      $options = $options->get();
      $type = $this->checkOpionsType($options);
      if ($type === 'IS_STRING') {
        foreach ($values as $value) {
          $res->push($options[$value]);
        }
      } elseif ($type === 'IS_KEY_VALUE') {
        foreach ($values as $value) {
          $text = null;
          foreach ($options as $entry) {
            if ($entry['value'] == $value) {
              $text = $entry['text'];
            }
          }
          $res->push($text);
        }
      }
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