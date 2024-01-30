<?php

namespace Tritrics\AflevereApi\v1\Data;

use ArrayIterator;
use IteratorAggregate;

/**
 * Handles array-like data and wraps it with handy functions.
 */
class Collection implements IteratorAggregate
{
  /**
   * The wrapped data
   * 
   * @var array
   */
  protected $data = [];

  /**
   * optionally give initial data
   */
  public function __construct ()
  {
    if (func_num_args() > 0) {
      $this->set(func_get_arg(0));
    }
  }

  /**
   * Delegate function calls to $data.
   */
  final public function __call (mixed $method, mixed $args): mixed
  {
    return call_user_func_array([$this->data, $method], $args);
  }

  /**
   * Make this class an iterator class.
   */
  final public function getIterator() : ArrayIterator
  {
    return new ArrayIterator($this->data);
  }

  /**
   * Find a (sub-)node with given key(s).
   */
  final public function node (...$keys): Collection
  {
    $key = array_shift($keys);
    if ($this->has($key)) {
      if (count($keys) > 0) {
        if ($this->data[$key] instanceof Collection) {
          return call_user_func_array(array($this->data[$key], 'node'), $keys);
        }
      } else {
        return $this->data[$key];
      }
    }
    return new Collection();
  }

  /**
   * Set the value of this node. Adds new Collections if given value is an array.
   */
  final public function set (mixed $mixed): void
  {
    if (is_array($mixed)) {
      if ( ! $this->isCollection()) {
        $this->data = [];
      }
      foreach ($mixed as $key => $value) {
        $this->add($key, $value);
      }
    } else {
      $this->data = $mixed;
    }
  }

  /**
   * Adds a new node to array, optionally set value with second argument.
   * Method fails if data isn't an array. Giving an array with keys will
   * add nesting nodes.
   */
  final public function add (mixed $keys): ?Collection
  {
    // $keys is an array of keys -> nested adding
    $key = is_array($keys) ? array_shift($keys) : $keys;
    if ( ! $this->isCollection() || ! $this->isKey($key)) {
      return null;
    }

    // more keys left, so create node and call this function again with
    // the rest of the keys
    if (is_array($keys) && count($keys)) {
      if ( ! isset($this->data[$key]) || ! $this->data[$key] instanceof Collection) {
        $this->data[$key] = new Collection();
      }
      $args = func_get_args();
      $args[0] = $keys;
      return call_user_func_array([ $this->data[$key], "add" ], $args);
    }

    // finally adding, depending if $value is given or not
    if (func_num_args() === 2) {
      $value = func_get_arg(1);
      if ($value instanceof Collection) {
        $this->data[$key] = $value;
      } else {
        $this->data[$key] = new Collection($value);
      }
    } else {
      $this->data[$key] = new Collection();
    }
    return $this->data[$key];
  }

  /**
   * Same like add() + set(), but for numerical index.
  */
  final public function push (mixed $mixed): Collection
  {
    if ( ! $this->isCollection()) {
      $this->data = [];
    }
    $this->data[] = new Collection($mixed);
    return end($this->data);
  }

  /**
   * Merge a Collection into $data (not a deep merge, simply top-level keys)
   */
  final public function merge (Collection $data): void
  {
    if ($this->isCollection() && $data->isCollection()) {
      foreach ($data as $key => $value) {
        $this->data[$key] = $value;
      }
    }
  }

  /**
   * Get the first element of an array.
   */
  final public function first(): Collection
  {
    return $this->node(0);
  }

  /**
   * Get value from $data
   */
  final public function get () : mixed
  {
    // $data is an array
    if ($this->isCollection()) {
      $childs = [];
      foreach ($this->data as $key => $value) {
        $childs[$key] = $value->get();
      }
      return $childs;
    }
    
    // single node, but object
    elseif (is_object($this->data) && method_exists($this->data, 'get')) {
      return $this->data->get();
    }
    
    // endpoint, single value
    else {
      return $this->data;
    }
  }

  /**
   * Check if a key in $data exists.
   */
  final public function has (string|int $key): bool
  {
    return $this->isCollection() && $this->isKey($key) && isset($this->data[$key]);
  }

  /**
   * Unset/delete a subnode of $data.
   */
  final public function unset (string|int $key): void
  {
    if ($this->isCollection() && isset($this->data[$key])) {
      unset ($this->data[$key]);
    }
  }

  /**
   * Compare $data with a given value.
   */
  final public function is (mixed $compare): bool
  {
    if (!$this->isCollection()) {
      return $this->data === $compare;
    }
    return false;
  }

  /**
   * Return the keys of $data.
   */
  final public function keys (): ?array
  {
    $data = $this->get();
    if(is_array($data)) {
      return array_keys($data);
    }
    return null;
  }

  /**
   * Check if $data is empty.
   */
  final public function isEmpty (): bool
  {
    return $this->data === [];
  }

  /**
   * Check, if $data is an array
   */
  final public function isCollection (): bool
  {
    return is_array($this->data);
  }

  /**
   * Check, if $data is a numeric array
   */
  final public function isNumeric (): bool
  {
    if (!$this->isCollection()) {
      return false;
    }
    return array_keys($this->data) === range(0, count($this->data) - 1);
  }

  /**
   * Get count of $data, if it's an array.
   */
  final public function count (): bool
  {
    if ($this->isCollection()) {
      return count($this->data);
    }
    return 0;
  }

  /**
   * Checks, if the given $key valid (string or integer).
   */
  private function isKey (mixed $check): bool
  {
    return ((is_string($check) && strlen($check) > 0) || (is_int($check) && $check >= 0));
  }
}