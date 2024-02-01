<?php

namespace Tritrics\AflevereApi\v1\Helper;

/**
 * Functions to convert string values like given in json-, post-data- or Kirby-objects
 * to numbers, strings or bool for intern use.
 */
class TypeHelper
{
  /**
   * Interprets numbers and strings and converts to the corresponding value type.
   * Can not detect 1 or 0 as bool, because of conflict with number.
   */
  public static function toChar(mixed $value, bool $trim = false, bool $lower = false): mixed
  {
    if (self::isUnhandledType($value)) {
      return $value;
    }
    if (self::isNumber($value)) {
      return self::toNumber($value);
    }
    return self::toString($value, $trim, $lower);
  }

  /**
   * Convert to string.
   */
  public static function toString(mixed $value, bool $trim = false, bool $lower = false): string
  {
    $value = (string) $value;
    $value = $trim ? trim($value) : $value;
    return $lower ? strtolower($value) : $value;
  }

  /**
   * Convert to number (float).
   */
  public static function toNumber(mixed $value): int|float
  {
    return (float) $value;
  }

  /**
   * Convert to integer.
   */
  public static function toInt(mixed $value): int
  {
    return (int) $value;
  }

  /**
   * Normalize to bool.
   */
  public static function toBool(mixed $value): bool
  {
    return self::isTrue($value) ? true : false;
  }

  /**
   * Check if $value is a string with optionally length check.
   * Numbers as strings are NOT evaluated as strings.
   */
  public static function isString(mixed $value, ?int $min = null, ?int $max = null): bool
  {
    if (!is_string($value) || self::isNumber($value)) {
      return false;
    }
    $len = strlen($value);
    $min = is_int($min) && $min >= 0 ? $min : $len;
    $max = is_int($max) && $max >= 0 ? $max : $len;
    return $len >= $min && $len <= $max;
  }

  /**
   * Check if $value is a number with optionally interval check.
   */
  public static function isNumber(mixed $value, ?int $min = null, ?int $max = null)
  {
    if (is_string($value) && preg_match('/^[-+]?[0-9]*\.?[0-9]+$/', $value)) {
      $value = self::toNumber($value);
    }
    if (!is_numeric($value)) {
      return false;
    }
    $min = is_numeric($min) ? $min : $value;
    $max = is_numeric($max) ? $max : $value;
    return $value >= $min && $value <= $max;
  }

  /**
   * Check if $value is a bool.
   */
  public static function isBool(mixed $value): bool
  {
    return self::isTrue($value) || self::isFalse($value);
  }

  /**
   * Check if $value is a true.
   */
  public static function isTrue(mixed $value): bool
  {
    return
      $value === true ||
      $value === 1 ||
      in_array(self::toString($value, true, true), ['1', 'true']);
  }

  /**
   * Check if $value is a false.
   */
  public static function isFalse(mixed $value): bool
  {
    return
      $value === false ||
      $value === 0 ||
      in_array(self::toString($value, true, true), ['0', 'false']);
  }

  /**
   * arrays, objects of bools can not be handled by string and number functions.
   */
  private static function isUnhandledType(mixed $value): bool
  {
    return
      is_object($value) ||
      is_array($value) ||
      is_bool($value);
  }
}
