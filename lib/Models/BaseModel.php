<?php

namespace Tritrics\Tric\v1\Models;

use Kirby\Cms\Languages;
use Kirby\Cms\Language;
use Kirby\Cms\Site;
use Kirby\Cms\Page;
use Kirby\Cms\File;
use Kirby\Cms\User;
use Kirby\Content\Field;
use Kirby\Cms\Block;
use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Helper\FieldHelper;

/**
 * Basic model for Kirby Fields and Models.
 * Inherits from Collection and adds some model functions.
 */
abstract class BaseModel extends Collection
{
  /**
   * the Kirby model instance
   * 
   * @var mixed
   */
  protected $model;

  /**
   * the Kirby Blueprint fragment
   * 
   * @var Collection
   */
  protected $blueprint;

  /**
   * 2-digit Language-code
   * 
   * @var ?string
   */
  protected $lang;

  /**
   * Optionally child-fields, auto-detected from blueprint
   * 
   * @var Collection
   */
  protected $fields;

  /**
   * Sometimes required for output control of child fields.
   * 
   * @var array|string
   */
  protected $addFields = 'all';

  /**
   * Marker if this model has child fields. Can be overwritten
   * by same property in child class.
   * 
   * @var bool
   */
  protected $hasChildFields = false;

  /**
   */
  public function __construct (
    Block|Field|User|File|Page|Site|Language|Languages $model,
    ?Collection $blueprint = null,
    ?string $lang = null,
    array|string $addFields = 'all'
  ) {
    $this->model = $model;
    $this->blueprint = $blueprint instanceof Collection ? $blueprint : new Collection();
    $this->lang = $lang;
    $this->addFields = $addFields;
    $this->setChildFields();
    $this->setModelData();
  }

  /**
   * Get the corresponding label for the selected option.
   */
  protected function getLabel(mixed $value): mixed
  {
    $options = $this->blueprint->node('options');
    if ($options instanceof Collection && $options->count() > 0) {
      $options = $options->get(false);
      $type = $this->getOpionsType($options);
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
   * Helper for fields with option-node: Kirby allowes different type of options.
   * (So far we can only handle static options.)
   */
  private function getOpionsType(array $options): ?string
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
    return null;
  }

  /**
   * Because Kirby sets multiple to true on default, we check for false here.
   * max = 1 is NOT interpreted as multiple, because the setting multiple
   * is explicitely designed for this.
   */
  protected function isMultiple(): bool
  {
    if ($this->blueprint->has('multiple') && $this->blueprint->node('multiple')->is(false)) {
      return false;
    }
    return true;
  }

  /**
   * Check and set possible child fields.
   */
  private function setChildFields (): void
  {
    $this->fields = new Collection();
    if ($this->hasChildFields && $this->blueprint->has('fields')) {
      error_log('-------------');

      // Inconsistency in Kirby's field definition
      // furthermore $this->lang is not documented and maybe not working for toObject()
      if ($this->blueprint->node('type')->is('object')) { 
        $fields = $this->model->toObject($this->lang)->fields();
      } else {
        $fields = $this->model->content($this->lang)->fields();
      }
      FieldHelper::addFields(
        $this->fields,
        $fields,
        $this->blueprint->node('fields'),
        $this->lang,
        $this->addFields
      );
      error_log(print_r($this->fields->get(), true));
    }
  }

  /**
   * Set the model properties.
   */
  private function setModelData (): void
  {
    // compute type
    if (method_exists($this, 'getType')) {
      $type = $this->getType();
    } else {
      $path = explode('\\', get_class($this));
      $class = array_pop($path);
      $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $class));
      $type = preg_replace('/(-model$)/', '', $name);
    }
    $this->add('type', $type);

    // properties, any kind of nodes
    if (method_exists($this, 'getProperties')) {
      $add = $this->getProperties();
      if ($add instanceof Collection) {
        $this->merge($add);
      }
    }

    // value
    if (method_exists($this, 'getValue')) {
      $value = $this->getValue();
      if ($value !== null) {
        $this->add('value', $value);
      }
    }

    // child-fields
    if ($this->fields->count() > 0) {
      $this->add('fields', $this->fields);
    }
  }
}