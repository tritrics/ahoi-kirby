<?php

namespace Tritrics\Api\Services;

class GlobalService
{
  /**
   * @param mixed $value
   * @param bool $trim trim string values
   * @return string|int|float|bool
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

  /** */
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
   * @param array $arr the array to normalise
   * @param array|bool $norm_values normalise all values or the given
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
