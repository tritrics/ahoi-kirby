<?php

namespace Tritrics\AflevereApi\v1\Services;

/**
 * Collection of globally used functions.
 */
class GlobalService
{
  /**
  * Normalize a value.
  *
  * @param Mixed $value 
  * @param Boolean $trim 
  * @param Boolean $strtolower 
  * @return Object|array|bool|float|string 
  */
  public static function typecast ($value, $trim = false, $strtolower = false)
  {
    if (is_object($value) || is_array($value) || is_bool($value)) {
      return $value;
    }
    $value = $trim ? trim($value) : $value;
    if (preg_match('/^[-+]?[0-9]*\.?[0-9]+$/', $value)) {
      return (float) $value;
    }
    $value = (string) $value;
    $value = $strtolower ? strtolower($value) : $value;
    return $value;
  }

  /**
   * Normalize a bool value.
   * 
   * @param Mixed $value 
   * @param Mixed $defaultReturn 
   * @return Mixed 
   */
  public static function typecastBool ($value, $defaultReturn = null)
  {
    if (
      $value === 1 ||
      $value === true ||
      strtolower(trim($value)) === 'true') {
        return true;
    } elseif (
      $value === 0 ||
      $value === false ||
      strtolower(trim($value)) === 'false') {
        return false;
    }
    return $defaultReturn;
  }

  /**
   * Normalize an array.
   *
   * @param Array $arr the array to normalise
   * @param Array|bool $norm_values normalise all values or the given
   * @return Array 
   */
  public static function normaliseArray ($arr, $norm_values = false)
  {
    $res = [];
    foreach ($arr as $key => $value) {
      $key = self::typecast($key, true, true);
      if (is_array($value)) {
        $res[$key] = self::normaliseArray(
          $value,
          (is_array($norm_values) && in_array($key, $norm_values)) ? true : $norm_values
        );
      } elseif ($norm_values === true || (is_array($norm_values) && in_array($key, $norm_values))) {
        $res[$key] = self::typecast($value, true, true);
      } else {
        $res[$key] = $value;
      }
    }
    return $res;
  }
}
