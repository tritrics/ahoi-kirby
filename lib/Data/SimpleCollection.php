<?php

namespace Tritrics\AflevereApi\v1\Data;

use ArrayIterator;

/**
 * Class to wrap instances for comfortable access like
 * $wrapper->prename->get()
 */
class SimpleCollection
{
  /**
   * List of instances like
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
    return new class
    {
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
}
