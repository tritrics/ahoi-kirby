<?php

namespace Tritrics\AflevereApi\v1\Post;

use ArrayIterator;
use Tritrics\AflevereApi\v1\Data\Collection;

/**
 * Class to wrap a set of post value instances.
 * Access: PostValues->prename->get()
 */
class PostValues
{
  /**
   * List of post value instances like
   * [ key => instance ]
   * 
   * @var array
   */
  private $data = [];

  /**
   */
  public function __construct(array $data)
  {
    if (!array_is_list($data)) {
      $this->data = $data;
    }
  }

  /**
   * Simply return the instance or an empty model.
   */
  public function __get(string $key): object
  {
    if (isset($this->data[$key])) {
      return $this->data[$key];
    }
    return new class {
      public function __get(mixed $key): mixed {
        return '';
      }
      public function __call(mixed $method, mixed $args): mixed {
        return '';
      }
    };
  }

  /**
   * Make this class an iterator class.
   */
  final public function getIterator(): ArrayIterator
  {
    return new ArrayIterator($this->data);
  }

  /**
   * Check if model exists.
   */
  public function has (string $key): bool
  {
    return isset($this->data[$key]);
  }

  /**
   * check for errors in post value instances
   */
  public function hasError(): bool
  {
    foreach ($this->data as $instance) {
      if ($instance->hasError()) {
        return true;
      }
    }
    return false;
  }

  /**
   * Get result object with fields, values and errors
   */
  public function getResult(bool $addValues): Collection
  {
    $res = new Collection();
    foreach ($this->data as $key => $instance) {
      $field = $res->add($key);
      if ($addValues) {
        $field->add('type', $instance->getType());
        $field->add('value', $instance->get());
      }
      $field->add('errno', $instance->getError());
    }
    return $res;
  }

  /**
   * For debugging purposes.
   */
  public function __toString(): string
  {
    $res = [];
    foreach ($this->data as $key => $instance) {
      $res[] = $key . ': ' . $instance->__toString();
    }
    return implode("\n", $res);
  }
}
