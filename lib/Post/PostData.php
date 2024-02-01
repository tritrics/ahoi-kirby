<?php

namespace Tritrics\AflevereApi\v1\Post;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\TypeHelper;

/**
 * Wrapper for post data with sanitizing and validation functions.
 */
class PostData
{
  /**
   * The action
   * 
   * @var string
   */
  private $action;

  /**
   * The field definitions for the action from config.php
   * 
   * @var array
   */
  private $def;

  /**
   * The post data, whereas only those values are read, which
   * are defined in $def. Structure:
   * 
   * [
   *   key => instance of value class
   * ]
   * 
   * @var array
   */
  private $fields;

  /**
   * The (global) error code
   * In addition each field might have an individual error.
   * 
   * @var int
   */
  private $errno = 0;

  /**
   * Availabe fieldtypes with corresponding classes.
   * 
   * @var array
   */
  private $valueClasses = [
    'string'  => '\Post\StringValue', // without linebreaks
    'text'    => '\Post\TextValue',   // with linebreaks
    'number'  => '\Post\NumberValue', // integers or floats
    'email'   => '\Post\EmailValue',
    'url'     => '\Post\UrlValue',
    'bool'    => '\Post\BoolValue',   // 0, 1, '0', '1', 'false', 'true', converts to bool
  ];

  /**
   */
  public function __construct (string $action, array $data)
  {
    $this->readDef($action);
    if (!$this->hasError()) {
      $this->readData($data);
      $this->validate();
    }
  }

  /**
   * Get Fields for use in templates.
   */
  public function get(): array
  {
    return $this->fields;
  }

  /**
   * General check for (any) error.
   */
  public function hasError (): bool
  {
    return $this->errno > 0;
  }

  /**
   * Get the error code.
   */
  public function getError (): int
  {
    return $this->errno;
  }

  /**
   * Get result object with fields, values and errors
   */
  public function getResult (bool $addValues): Collection
  {
    $res = new Collection();
    foreach ($this->fields as $key => $class) {
      $field = $res->add($key);
      if ($addValues) {
        $field->add('type', $class->getType());
        $field->add('value', $class->get());
      }
      $field->add('errno', $class->getError());
    }
    return $res;
  }

  /**
   * Read field definitions from config.php.
   */
  private function readDef(string $action): void
  {
    $this->action = TypeHelper::toString($action, true, true);
    if (strlen($this->action)) {
      $def = ConfigHelper::getConfig('actions.' . $this->action . '.input');
    }
    if (!is_array($def)) {
      $this->errno = 17;
    } else {
      $this->def = $def;
    }
  }

  /**
   * Take those fields from $post to $fields, that are defined in $def
   * and skip all the rest. Sanitize values first and validate after.
   */
  private function readData (array $data): void
  {
    // sanitize field names, collect valid fields
    $fields = [];
    foreach ($data as $key => $value) {
      $key = TypeHelper::toString($key, true, true);
      if ($this->isValidField($key)) {
        $fields[$key] = $value;
      }
    }

    // create classes for every field in def and pass post-value
    foreach ($this->def as $key => $def) {
      $type = $this->def[$key]['type'];
      $value = $fields[$key];
      if (substr($type, -2) === '[]') {
      } else {
        $class = ConfigHelper::getNamespace() . $this->valueClasses[$type];
        $this->fields[$key] = new $class($value, $this->def[$key]);
      }
    }
  }

  /**
   * Check values for validation error and set $errno.
   */
  private function validate (): void
  {
    foreach ($this->fields as $key => $class) {
      if ($class->hasError()) {
        $this->errno = 110;
      }
    }
  }

  /**
   * Check if a field (key in post-data) is defined.
   */
  private function isValidField (string $key): bool
  {
    if (
      !isset($this->def[$key]) ||
      !is_array($this->def[$key]) ||
      !isset($this->def[$key]['type']) ||
      !is_string($this->def[$key]['type'])
    ) {
      return false;
    }
    return isset($this->valueClasses[$this->def[$key]['type']]);
  }

  /**
   * For debugging purposes.
   */
  public function __toString(): string
  {
    $res = [];
    foreach ($this->fields as $key => $class) {
      $res[] = $key . ': ' . $class->__toString();
    }
    return implode("\n", $res);
  }
}
    