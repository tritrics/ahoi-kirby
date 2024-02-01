<?php

namespace Tritrics\AflevereApi\v1\Post;

use Tritrics\AflevereApi\v1\Helper\ConfigHelper;

/**
 * Basic class for post and meta value.
 * Interface for use in templates.
 */
class BaseValue
{
  /**
   * @var string
   */
  protected $value;

  /**
   */
  public function __construct(mixed $value)
  {
    $this->value = $value;
  }

  /**
   * Get sanitized value.
   */
  public function get(bool $html = false): mixed
  {
    return $this->value;
  }

  /**
   * Check if value is existing. For use in templates.
   */
  public function is(): bool
  {
    return !empty($this->value);
  }

  /**
   * For using the class in templates like <?= $class ?>.
   */
  public function __toString(): string
  {
    return (string) $this->get();
  }
}
