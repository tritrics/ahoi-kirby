<?php

namespace Tritrics\AflevereApi\v1\Post;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\TypeHelper;

/**
 * Wrapper for post data with sanitizing and validation functions.
 */
abstract class PostData extends Collection
{
  /**
   * The action
   * 
   * @var string
   */
  private $action;

  /**
   * 2-digit Language-code
   * 
   * @var ?string
   */
  protected $lang;

  /**
   * The field definitions for the action from config.php
   * 
   * @var array
   */
  private $def;

  /**
   * The original post data
   * 
   * @var array
   */
  private $post;

  /**
   * The sanitized, evaluated post data
   * 
   * @var Collection
   */
  private $fields;

  /**
   * The (global) error code
   * In addition each field might have an individual error.
   * 
   * @var int
   */
  private $errno = 0;

  private $fieldTypes = [
    'string', // without linebreaks
    'text',   // with linebreaks
    'number', // integers or floats
    'email',
    'url',
    'bool',   // 0, 1, '0', '1', 'false', 'true', converts to bool
  ];

  /**
   */
  public function __construct (string $action, array $post)
  {
    $this->action = $action;
    $this->post = $post;
    if (strlen($action)) {
      $this->def = ConfigHelper::getConfig('actions.' . $action . '.input');
    }
    if (!is_array($this->def)) {
      $this->errno = 45;
    }
    $this->readData();
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

  public function validate()
  {
    if ($this->hasError()) {
      return;
    }
  }

  /**
   * Take those fields from $post to $fields, that are defined in $def
   * and skip all the rest. Sanitize values first and validate after.
   */
  private function readData(): void
  {
    $stripTags =        ConfigHelper::getConfig('form-security.strip-tags', true);
    $stripBackslashes = ConfigHelper::getConfig('form-security.strip-backslashes', true);
    $stripUrls =        ConfigHelper::getConfig('form-security.strip-urls', true);
    foreach($this->data as $key => $value) {
      $key = TypeHelper::toString($key, true, true);

      if (!$this->isValidField($key)) {
        continue;
      }
      if (is_array($value))
    }

    if ($this->isString($value)) {
      if ($stripTags) {
        $value = strip_tags($value);
      }
      if ($stripBackslashes) {
        $value = str_replace('\\', '', $value);
      }
      if ($stripUrls) {
        $value = preg_replace('/(https?:\/\/([-\w\.]+[-\w])+(:\d+)?(\/([\w\/_\.#-]*(\?\S+)?[^\.\s])?)?)/', '[link removed]', $value);
      }
      $value = trim($value);
    }
    $res[$key] = $value;
  }

  private function readField ($value) {
    if (is_array($value)) {
      return '';
    }

  }

  private function isValidField (string $key): bool
  {
    if (
      !isset($this->def[$key]) ||
      !is_array($this->def[$key]) ||
      !isset($this->def[$key]['type']) ||
      !$this->isString($this->def[$key]['type'])
    ) {
      return false;
    }
      in_array($this->def[$key]['type'], $this->fieldtypes);
  }
}
    