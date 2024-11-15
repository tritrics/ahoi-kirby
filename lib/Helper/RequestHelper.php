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
    $valid_actions = ConfigHelper::get('actions');
    return strlen($action) && isset($valid_actions[$action]) ? $action : null;
  }

  /**
   * Get fields parameter from Request.
   */
  public static function getFields(Request $request): array
  {
    $fields = $request->get('fields');
    $res = [];
    if (is_array($fields)) {
      $res = array_map(function ($entry) {
        return TypeHelper::toString($entry, true, true);
      }, $fields);
      $res = array_filter($res, function ($entry) {
        return strlen($entry) > 0;
      });
    }
    return array_unique($res);
  }

  /**
   * Get the filter option for collection request. Can be an array with strings
   * 
   * [ 'created', 'date >', '2023-08-15' ]
   * 
   * or multiple of these:
   * 
   * [
   *   [ 'tags', 'webdesign', ',' ],
   *   [ 'created', 'date >', '2023-08-15' ]
   * ]
   */
  public static function getFilter(Request $request): array
  {
    $filter = $request->get('filter');
    if (!is_array($filter)) {
      return [];
    }

    // single line
    if (TypeHelper::isString($filter[0]) || TypeHelper::isNumber($filter[0])) {
      $filter = [ $filter ];
    }

    // check and optionally convert all values
    $res = [];
    if (is_array($filter[0])) {
      foreach ($filter as $line) {
        $args = [];
        foreach ($line as $arg) {
          if (TypeHelper::isString($arg)) {
            $args[] = $arg;
          } else if (TypeHelper::isNumber($arg)) {
            $args[] = TypeHelper::toString($arg);
          } else if(TypeHelper::isTrue($arg)) {
            $args[] = 'true';
          } else if (TypeHelper::isFalse($arg)) {
            $args[] = 'false';
          } else {
            continue 2; // discard complete line
          }
        }
        $res[] = $args;
      }
    }
    return $res;
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
  public static function getLimit(Request $request): int
  {
    $val = TypeHelper::toInt($request->get('limit'));
    $res = (!$val || $val <= 0) ? 10 : $val;
    return $res;
  }

  /**
   * Get set parameter from Request, any number > 0, default 1.
   */
  public static function getOffset(Request $request): int
  {
    $val = TypeHelper::toInt($request->get('offset'));
    return (!$val || $val <= 0) ? 0 : $val;
  }

  /**
   * Get the sort option for collection request. Can be an array with strings
   * 
   * [ 'tags', 'asc' ]
   * 
   * or multiple of these:
   * 
   * [
   *   [ 'tags', 'asc' ],
   *   [ 'created', 'desc', 'SORT_LOCALE_STRING' ]
   * ]
   */
  public static function getSort(Request $request): array
  {
    $sort = $request->get('sort');
    if (!is_array($sort)) {
      return [];
    }

    // check all values
    $res = [];
    foreach ($sort as $arg) {
      if (TypeHelper::isString($arg)) {
        $res[] = TypeHelper::toString($arg, true, true);
      }
    }
    return $res;
  }

  /**
   * Get status parameter from Request.
   */
  public static function getStatus(Request $request): string
  {
    $val = TypeHelper::toString($request->get('status'), true, true);
    if (in_array($val, ['listed', 'unlisted', 'published'])) {
      return $val;
    }
    return 'listed';
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

  /**
   * Expects an multidimensional array like:
   * 
   * [ [ $val1, $val2, ... ], [ $val1, $val2, ... ] ]
   * 
   * and checks if all values are strings or numbers. If any value
   * doesn't pass the test, the complete array is discarded.
   */
  private static function normalizeArray(array $arr): array
  {
    
    return $arr;
  }
}
