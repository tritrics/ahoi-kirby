<?php

namespace Tritrics\AflevereApi\v1\Services;

use Tritrics\AflevereApi\v1\Services\BlueprintService;

/**
 * Reads an normalizes request parameter from the API request.
 */
class RequestService
{
  /**
   * Get page parameter from Request, any number > 0, default 1.
   * 
   * @param Mixed $request 
   * @return Integer 
   */
  public static function getPage ($request)
  {
    $val = intval($request->get('page'));
    if ( ! is_int($val) || $val <= 0) {
      $val = 1;
    }
    return $val;
  }

  /**
   * Get limit parameter from Request, any number > 0, default 10.
   * 
   * @param Mixed $request 
   * @return Integer 
   */
  public static function getLimit ($request)
  {
    $val = intval($request->get('limit'));
    if ( ! is_int($val) || $val <= 0) {
      $val = 10;
    }
    return $val;
  }

  /**
   * Get order parameter from Request, asc or desc, default desc.
   * 
   * @param Mixed $request 
   * @return String [ asc, desc ] 
   */
  public static function getOrder ($request)
  {
    $val = strval($request->get('order'));
    $val = strtolower(trim($val));
    if (is_string($val) && in_array($val, ['asc', 'desc'])) {
      return $val;
    }
    return 'asc';
  }

  /**
   * Get fields parameter from Request, can be 'all' or array with field-names.
   * 
   * @param Mixed $request 
   * @return String|Array 
   */
  public static function getFields ($request)
  {
    $val  = $request->get('fields');
    if (is_string($val) && strtolower(trim($val)) === 'all') {
      return 'all';
    }
    if (!is_array($val) || count($val) === 0) {
      return [];
    }
    $val = array_map(function ($entry) {
      return preg_replace("/[^a-z0-9_-]/", "", strtolower(trim($entry))); 
    }, $val);
    $val = array_filter($val, function ($entry) {
      return (is_string($entry) && strlen($entry) > 0);
    });
    return $val;
  }

  /**
   * Debugging-Function to stop execution for x sec.
   * Helpful to test frontend behaviour for async requests.
   * Limited to 10 sec.
   * 
   * @param Mixed $request 
   * @return Integer 
   */
  public static function getSleep($request)
  {
    $val = intval($request->get('sleep'));
    if (kirby()->option('debug', false) && is_int($val) && $val > 0 && $val <= 10) {
      sleep($val);
    }
    return $val;
  }

  /**
   * Parse the request like field.eq.foo to array.
   * Attention: the first parameter "field" is the fieldname, where as
   * compare() uses the value of the field.
   * 
   * @param Mixed $request 
   * @return Null|Array
   */
  public static function getFilter ($request)
  {
    $val = $request->get('filter');
    if (!$val) {
      return null;
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
        preg_replace("/[^a-z0-9_-]/", "", strtolower(trim(array_shift($query)))),
        preg_replace("/[^a-z]/", "", strtolower(trim(array_shift($query)))),
        implode('.', $query)
      ];
    }
    return count($res) > 0 ? $res : null;
  }

  /**
   * Filter children by criteria like fieldname.eq.value
   * 
   * @param Page $page 
   * @param Array $filter
   * @return Mixed 
   * 
   * @TODO: Convert the different value-types (number, string, date) and make them comparable
   */
  public static function filterChildren ($page, $filter)
  {
    $children =  $page->children()->filter( // the Kirby-filter-function of children()
      function($child) use ($filter) {
        $blueprint = BlueprintService::getBlueprint($child);
        foreach ($filter as $criteria) {
          $fieldname = $criteria[0];

          // Special cases
          if ($fieldname === 'blueprint') {
            if (!self::compare($blueprint->name(), $criteria[1], $criteria[2])) {
              return false;
            }
          }

          // Field
          if (!$child->content()->has($fieldname)) {
            continue;
          }
          $field = $child->$fieldname();
          $fieldDef = $blueprint->node('fields', $fieldname);
          if (!$fieldDef) {
            continue;
          }
          switch ($fieldDef->node('type')->get()) {
            // case 'date':
            // case ...
            default:
              $value = (string) $field->get();
          }
          if (!self::compare($value, $criteria[1], $criteria[2])) {
            return false;
          }
        }
        return true;
      }
    );
    return $children;
  }

  /**
   * Comparing values
   * 
   * @param Mixed $value 
   * @param String $operator 
   * @param Mixed $compare 
   * @return Boolean 
   */
  public static function compare ($value, $operator, $compare)
  {
    $value = GlobalService::typecast($value, true, true);
    $operator = GlobalService::typecast($operator, true, true);
    $compare = GlobalService::typecast($compare, true, true);
    if ($value === null || $operator === null || $compare === null) {
      return false;
    }
    switch ($operator) {
      case 'eq':
        return $value === $compare;
        break;
      case 'nt':
        return $value !== $compare;
        break;
      case 'gt':
        return $value > $compare;
        break;
      case 'gte':
        return $value >= $compare;
        break;
      case 'lt':
        return $value < $compare;
        break;
      case 'lte':
        return $value <= $compare;
        break;
      // ... more like startswith, contains, endswith
    }
    return true;
  }
}
