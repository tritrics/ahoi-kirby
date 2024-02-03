<?php

namespace Tritrics\AflevereApi\v1\Post;

use Tritrics\AflevereApi\v1\Helper\TypeHelper;

class StringValue extends BaseValue
{
  /**
   * Sanitize, validate and store the given value.
   */
  protected function read (mixed $value): void
  {
    // check for data type, numbers are also allowed in text fields
    if (!TypeHelper::isString($value) && !TypeHelper::isNumber($value)) {
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
      if (preg_match('/\R/', $value)) {
        $this->errno = 120; // @errno120
        return;
      }
      if (isset($this->def['min']) && is_int($this->def['min']) && strlen($value) < $this->def['min']) {
        $this->errno = 122; // @errno122
        return;
      }
      if (isset($this->def['max']) && is_int($this->def['max']) && strlen($value) > $this->def['max']) {
        $this->errno = 122; // @errno122
        return;
      }
    }

    $this->value = $this->sanitizeString($value);
    $this->errno = 0;
  }
}