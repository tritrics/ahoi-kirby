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

    $entries = $this->getValue();

    // add info for pages, files, users
    if (in_array($type, ['pages', 'files', 'images', 'users'])) {
      $meta = new Collection();
      $meta->add('multiple', $this->isMultiple());
      $meta->add('count', $entries instanceof Collection ? $entries->count() : 0);
      $this->add('meta', $meta);
    }
    if ($type !== 'page') {
      $this->add('value', $entries);
    }
  }

  /**
   * Because Kirby sets multiple to true on default, we check for false here.
   * max = 1 is NOT interpreted as multiple, because the setting multiple
   * is explicitely designed for this. 
   */
  private function isMultiple ()
  {
    if($this->blueprint->has('multiple') && $this->blueprint->node('multiple')->is(false)) {
      return false;
    }
    return true;
  }

  /**
   * Get the corresponding label for the selected option
   */
  protected function getLabel($value)
  {
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