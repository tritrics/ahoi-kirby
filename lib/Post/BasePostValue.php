<?php

namespace Tritrics\AflevereApi\v1\Post;

use Tritrics\AflevereApi\v1\Helper\ConfigHelper;

/**
 * BaseValue for use as post value.
 */
class BasePostValue extends BaseValue
{
  /**
   * @var array
   */
  protected $def;

  /**
   * @var int
   */
  protected $errno;

  /**
   */
  public function __construct(mixed $value, array $def)
  {
    $this->def = $def;
    $this->read($value);
  }

  /**
   * Get data type
   */
  public function getType (): string
  {
    $path = explode('\\', get_class($this));
    return strtolower(str_replace('Value', '', array_pop($path)));
  }
  
  /**
   * General check for (any) error.
   */
  public function hasError(): bool
  {
    return $this->errno > 0;
  }

  /**
   * Get error code.
   */
  public function getError(): int
  {
    return $this->errno;
  }

  /**
   * To be overwritten with validation logic.
   */
  protected function read (mixed $value): void
  {
    $this->value = $value;
  }

  /**
   * Sanitize strings like defined in config.php.
   */
  protected function sanitizeString(string $value): string
  {
    if (ConfigHelper::getConfig('form-security.strip-tags', true)) {
      $value = strip_tags($value);
    }
    if (ConfigHelper::getConfig('form-security.strip-backslashes', true)) {
      $value = str_replace('\\', '', $value);
    }
    return $value;
  }
}
