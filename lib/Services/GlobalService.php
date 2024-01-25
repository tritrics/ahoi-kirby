<?php

namespace Tritrics\AflevereApi\v1\Services;

/**
 * Collection of globally used functions.
 */
class GlobalService
{
  /**
   * Backend and frontend host infos.
   * 
   * @var array
   */
  private static $hosts = [];

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

  /**
   * Get host inforamtion about API and frontend.
   * 
   * @param String|Null $lang 
   * @return Array
   */
  public static function getHosts ($lang = null)
  {
    if (!$lang) {
      $lang = '__default__';
    }
    if (!isset(self::$hosts[$lang])) {
      self::$hosts[$lang] = [];
      self::$hosts[$lang]['self'] = [];
      self::$hosts[$lang]['referer'] = [];
      $backend = self::parseUrl(site()->url($lang));
      self::$hosts[$lang]['self']['host'] = $backend['host'];
      self::$hosts[$lang]['self']['port'] = isset($backend['port']) ? $backend['port'] : null;
      if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = self::parseUrl($_SERVER['HTTP_REFERER']);
        self::$hosts[$lang]['referer']['host'] = $referer['host'];
        self::$hosts[$lang]['referer']['port'] = isset($referer['port']) ? $referer['port'] : null;
      } else {
        self::$hosts[$lang]['referer']['host'] = self::$hosts[$lang]['self']['host'];
        self::$hosts[$lang]['referer']['port'] = self::$hosts[$lang]['self']['port'];
      }
      self::$hosts[$lang]['referer']['ip'] = isset($_SERVER['HTTP_CLIENT_IP'])
        ? $_SERVER['HTTP_CLIENT_IP']
        : (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
          ? $_SERVER['HTTP_X_FORWARDED_FOR']
          : $_SERVER['REMOTE_ADDR']);
    }
    return self::$hosts[$lang];
  }

  /**
   * Parsing url in parts.
   * 
   * @param String $href 
   * @return Array|String|Integer|Boolean|Null 
   */
  public static function parseUrl($href)
  {
    $parts = parse_url($href);

    // doing some normalization
    if (isset($parts['scheme'])) {
      $parts['scheme'] = strtolower($parts['scheme']);
    }
    if (isset($parts['host'])) {
      $parts['host'] = strtolower($parts['host']);
    }
    if (isset($parts['port'])) {
      $parts['port'] = (int) $parts['port'];
    }
    if (isset($parts['path'])) {
      $parts['path'] = trim(strtolower($parts['path']), '/');
      if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['mailto', 'tel'])) {
        $parts['path'] = '/' . $parts['path'];
      }
    }
    if (isset($parts['fragment'])) {
      if (strpos($parts['fragment'], '?') === false) {
        $hash = $parts['fragment'];
        $query = null;
      } else {
        $hash = substr($parts['fragment'], 0, strpos($parts['fragment'], '?') - 1);
        $query = substr($parts['fragment'], strpos($parts['fragment'], '?'));
      }
      if (!empty($hash)) {
        $parts['hash'] = $hash;
      }
      if (!empty($query)) {
        $parts['query'] = $query;
      }
      unset($parts['fragment']);
    }
    return $parts;
  }

  /**
   * Build the url, reverse of parseUrl().
   * 
   * @param Array $parts 
   * @return String 
   */
  public static function buildUrl($parts)
  {
    return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
      ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
      (isset($parts['user']) ? "{$parts['user']}" : '') .
      (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
      (isset($parts['user']) ? '@' : '') .
      (isset($parts['host']) ? "{$parts['host']}" : '') .
      (isset($parts['port']) ? ":{$parts['port']}" : '') .
      (isset($parts['path']) ? "{$parts['path']}" : '') .
      (isset($parts['hash']) ? "#{$parts['hash']}" : '') .
      (isset($parts['query']) ? "?{$parts['query']}" : '');
  }
}
