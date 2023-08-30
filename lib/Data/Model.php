<?php

namespace Tritrics\Api\Data;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Services\FieldService;

/** */
abstract class Model extends Collection
{
  /**
   * the Kirby model instance
   */
  protected $model;

  /**
   * the Kirby Blueprint fragment
   */
  protected $blueprint;

  /**
   * Language-code
   */
  protected $lang;

  /**
   * Optionally child-fields, auto-detected from blueprint
   */
  protected $fields;

  /** */
  protected $hasChildFields = false;

  /**
   * @param mixed $model, can be instance of KirbyField or value
   * @param Collection $blueprint
   * @param string $lang
   */
  public function __construct ($model, $blueprint, $lang)
  {
    $this->model = $model;
    $this->blueprint = $blueprint instanceof Collection ? $blueprint : new Collection();
    $this->lang = $lang;
    $this->setChildFields();
    $this->setModelData();
  }

  /** */
  private function setChildFields ()
  {
    $this->fields = new Collection();
    if ($this->hasChildFields && $this->blueprint->has('fields')) {

      // Inconsistency in Kirby's field definition
      // furthermore $this->lang is not documented and maybe not working for toObject()
      if ($this->blueprint->node('type')->is('object')) { 
        $fields = $this->model->toObject($this->lang)->fields();
      } else {
        $fields = $this->model->content($this->lang)->fields();
      }
      FieldService::addFields(
        $this->fields,
        $fields,
        $this->blueprint->node('fields'),
        $this->lang
      );
    }
  }

  /**
   * Get the model value
   * 
   * @return Collection|string|int|float|bool
   */
  abstract protected function getValue ();

  /**
   * set the model properties
   */
  private function setModelData ()
  {
    if (method_exists($this, 'getType')) {
      $type = $this->getType();
    } else {
      $path = explode('\\', get_class($this));
      $class = array_pop($path);
      $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $class));
      $type = preg_replace('/(-model$)/', '', $name);
    }
    $this->add('type', $type);

    if (method_exists($this, 'getProperties')) {
      $add = $this->getProperties();
      if ($add instanceof Collection) {
        $this->merge($add);
      }
    }

    $mixed = $this->getValue();
    if ($mixed instanceof Collection && $mixed->isNumeric()) {
      if ($this->isMultiple()) {
        $this->add('count', $mixed->count());
      } else {
        $mixed = $mixed->node(0);
      }
    }
    if ($mixed !== null) {
      $res = $this->add('value', $mixed);
    }
  }

  /**
   * multiple is true on default, but null returned
   */
  private function isMultiple ()
  {
    if ($this->blueprint->node('multiple') === false || $this->blueprint->node('max') === 1) {
      return false;
    }
    return true;
  }

  /**
   * Helper for fields with option-node:
   * Kirby allowes different type of options.
   * So far we can only handle static options.
   * 
   * @return string|null
   */
  protected function checkOpionsType ($options)
  {
    $values = array_values($options);
    if (isset($values[0])) {

      // for numeric keys Kirby uses options-def like:
      // - value: '100'
      //   text: Design
      // - value: '200'
      //   text: Architecture
      if (is_array($values[0]) && isset($values[0]['value']) && isset($values[0]['text'])) {
        return 'IS_KEY_VALUE';
      }
      
      // string keys like
      // - design: Design
      // - architecture: Architecture
      // or like
      // - center
      // - middle
      else if (is_string($values[0])) {
        return 'IS_STRING';
      }
    }
  }
}