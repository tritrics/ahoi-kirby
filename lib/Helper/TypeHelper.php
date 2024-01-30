<?php

namespace Tritrics\AflevereApi\v1\Helper;

/**
 * Functions to convert values.
 */
class TypeHelper
{
  /**
   * Normalize a value.
   */
  public static function auto(
    mixed $value,
    bool $trim = false,
    bool $strtolower = false
  ): mixed {
    if (is_object($value) || is_array($value) || is_bool($value)) {
      return $value;
    }
    if (preg_match('/^[-+]?[0-9]*\.?[0-9]+$/', $value)) {
      return self::float($value);
    }
    return self::string($value, $trim, $strtolower);
  }

  /**
   * Normalize to string.
   */
  public static function string(
    mixed $value,
    bool $trim = false,
    bool $strtolower = false
  ): string {
    $value = (string) $value;
    if ($trim) {
      $value = trim($value);
    }
    if ($strtolower) {
      $value = strtolower($value);
    }
    return $value;
  }

  /**
   * Normalize to number (float).
   */
  public static function float(mixed $value): float
  {
    return (float) $value;
  }

  /**
   * Normalize to number (float).
   */
  public static function int(mixed $value): int
  {
    return (int) $value;
  }

  /**
   * Normalize to bool.
   */
  public static function bool(mixed $value, mixed $default_return = false): mixed
  {
    if (
      $value === 1 ||
      $value === true ||
      strtolower(trim($value)) === 'true'
    ) {
      return true;
    } elseif (
      $value === 0 ||
      $value === false ||
      strtolower(trim($value)) === 'false'
    ) {
      return false;
    }
    return $default_return; // !important
  }

  /**
   * Normalize values in array.
   */
  public static function array(
    array $arr,
    array|bool $norm_values = false // bool: normalize all/none subarray, array: normalize the given nodes
  ): array {
    $res = [];
    foreach ($arr as $key => $value) {
      $key = self::auto($key, true, true);
      if (is_array($value)) {
        $res[$key] = self::array(
          $value,
          (is_array($norm_values) && in_array($key, $norm_values)) ? true : $norm_values
        );
      } elseif ($norm_values === true || (is_array($norm_values) && in_array($key, $norm_values))) {
        $res[$key] = self::auto($value, true, true);
      } else {
        $res[$key] = $value;
      }
    }
    return $res;
  }
}
