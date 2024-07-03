<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\Languages;
use Kirby\Cms\Language;
use Kirby\Cms\Site;
use Kirby\Cms\Page;
use Kirby\Cms\File;
use Kirby\Cms\User;
use Kirby\Content\Field;
use Kirby\Cms\Block;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\FieldHelper;

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
   * @var Collection|null
   */
  protected $fields = null;

  /**
   * Sometimes required for output control of child fields.
   * 
   * @var array|string
   */
  protected $addFields = 'all';

  /**
   * Marker if this model has child fields.
   * Can be overwritten by child class.
   * 
   * @var bool
   */
  protected $hasChildFields = false;

  /**
   * Name of the node with the value/childfields etc.
   * Can be overwritten by child class.
   */
  protected $valueNodeName = 'value';

  /**
   * Can be used, if model should output different content in different cases.
   */
  protected $addDetails = false;

  /**
   */
  public function __construct (
    Block|Field|User|File|Page|Site|Language|Languages $model,
    ?Collection $blueprint = null,
    ?string $lang = null,
    array|string $addFields = 'all',
    ?bool $addDetails = false
  ) {
    $this->model = $model;
    $this->addDetails = $addDetails;
    $this->blueprint = $blueprint instanceof Collection ? $blueprint : new Collection();
    $this->lang = $lang;
    $this->addFields = is_array($addFields) || $addFields === 'all' ? $addFields : 'all';
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
   * Get field type.
   * Optionally overwritten by child class.
   */
  protected function getType(): string
  {
    $path = explode('\\', get_class($this));
    $class = array_pop($path);
    $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $class));
    return preg_replace('/(-model$)/', '', $name);
  }

  /**
   * Get the data/value/childfields etc.
   * Overwritten by child class.
   */
  protected function getValue(): mixed
  {
    return null;
  }

  /**
   * Check and set possible child fields.
   */
  private function setChildFields (): void
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
      FieldHelper::addFields(
        $this->fields,
        $fields,
        $this->blueprint->node('fields'),
        $this->lang,
        $this->addFields
      );
    }
  }

  /**
   * Set the model properties.
   */
  private function setModelData (): void
  {
    $this->add('type', $this->getType());

    // properties, any kind of nodes
    if (method_exists($this, 'getProperties')) {
      $add = $this->getProperties();
      if ($add instanceof Collection) {
        $this->merge($add);
      }
    }

    // value
    $value = $this->getValue();
    if ($value instanceof Collection) {
      if ($value->count() > 0) {
        $this->add($this->valueNodeName, $value);
      }
    } else if ($value !== null) {
      $this->add($this->valueNodeName, $value);
    }
  }
}