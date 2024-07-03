<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Http\Request;

/**
 * Reads an normalizes request parameter from the API request.
 */
class RequestHelper
{
  /**
   * Normalize and check Action
   */
  public static function getAction(mixed $val): ?string
  {
    $action = TypeHelper::toString($val, true, true);
    $valid_actions = ConfigHelper::getConfig('actions');
    return strlen($action) && isset($valid_actions[$action]) ? $action : null;
  }
  
  /**
   * Normalize and check lang-code.
   * Returns empty string in a single-language installation. 
   */
  public static function getLang(mixed $val): ?string
  {
    $lang = TypeHelper::toString($val, true, true);
    if (!LanguagesHelper::isValid($lang)) {
      return null;
    }
    return strlen($lang) ? $lang : '';
  }

  /**
   * Get limit parameter from Request, any number > 0, default 10.
   */
  public static function getLimit (Request $request): int
  {
    
    $val = TypeHelper::toInt($request->get('limit'));
    $res = (!$val || $val <= 0) ? 10 : $val;
    return $res;
  }

  /**
   * Get fields parameter from Request, can be 'all' or array with field-names.
   */
  public static function getFields (Request $request): string|array
  {
    $val  = $request->get('fields');
    if (!is_array($val) || count($val) === 0) {
      return 'all';
    }
    $val = array_map(function ($entry) {
      return preg_replace("/[^a-z0-9_-]/", "", TypeHelper::toString($entry, true, true)); 
    }, $val);
    $val = array_filter($val, function ($entry) {
      return (is_string($entry) && strlen($entry) > 0);
    });
    return $val;
  }

  /**
   * Parse the request like field.eq.foo to array.
   * Attention: the first parameter "field" is the fieldname, where as
   * compare() uses the value of the field.
   */
  public static function getFilter (Request $request): array
  {
    $val = $request->get('filter');
    if (!$val) {
      return [];
    }
    if (!is_array($val)) {
      $val = [ $val ];
    }
    $res = [];
    foreach ($val as $string) {
      $query = explode('.', $string);
      if (count($query) < 3) { // field.eq. is possible, but needs the last .
        continue;
      }
      $res[] = [
        preg_replace("/[^a-z0-9_-]/", "", TypeHelper::toString(array_shift($query), true, true)),
        preg_replace("/[^a-z]/", "", TypeHelper::toString(array_shift($query), true, true)),
        implode('.', $query)
      ];
    }
    return $res;
  }

  /**
   * Get order parameter from Request, asc or desc, default desc.
   */
  public static function getOrder(Request $request): string
  {
    $val = TypeHelper::toString($request->get('order'), true, true);
    if (in_array($val, ['asc', 'desc'])) {
      return $val;
    }
    return 'asc';
  }

  /**
   * Get set parameter from Request, any number > 0, default 1.
   */
  public static function getSet(Request $request): int
  {
    $val = TypeHelper::toInt($request->get('set'));
    return (!$val || $val <= 0) ? 1 : $val;
  }

  /**
   * Parse the given path and return action.
   * @see parsesPath()
   */
  public static function parseAction(string $path, bool $multilang): array
  {
    $parts = array_filter(explode('/', $path));
    $lang = $multilang ? array_shift($parts) : null;
    $action = array_shift($parts);
    $token = count($parts) > 0 ? array_shift($parts) : null;
    return [$lang, $action, $token];
  }

  /**
   * Parse the given path and return language and node. In a multi language
   * installation, the first part of the path must be a valid language (which
   * is not validated here).
   * 
   * single language installation:
   * "/" -> site
   * "/some/page" -> page
   * 
   * multi language installation:
   * "/en" -> english version of site
   * "/en/some/page" -> english version of page "/some/path"
   */
  public static function parsePath(string $path, bool $multilang): array
  {
    $parts = array_filter(explode('/', $path));
    $lang = $multilang ? array_shift($parts) : null;
    $slug = count($parts) > 0 ? implode('/', $parts) : null;
    return [$lang, $slug];
  }
}
