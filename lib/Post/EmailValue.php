<?php

namespace Tritrics\AflevereApi\v1\Post;

use Tritrics\AflevereApi\v1\Helper\TypeHelper;

class EmailValue extends BaseValue
{
  /**
   * Sanitize, validate and store the given value.
   */
  protected function read(mixed $value): void
  {
    // check for data type
    if (!TypeHelper::isString($value)) {
      $this->errno = 120; // @errno120
      return;
    }

    // required check
    $value = trim($value);
    if (empty($value) && isset($this->def['required']) && $this->def['required'] === true) {
      $this->errno = 121; // @errno121
      return;
    }
    
    // content checks only apply if value is not empty
    if (!empty($value)) {
      if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $this->errno = 120; // @errno120
        return;
      }
    }

    $this->value = $value;
    $this->errno = 0;
  }
}
