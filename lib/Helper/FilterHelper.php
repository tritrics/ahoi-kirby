<?php

namespace Tritrics\Tric\v1\Helper;

use Kirby\Cms\Page;
use Kirby\Cms\Pages;

/**
 * Filter pages
 */
class FilterHelper
{
  /**
   * Comparing values
   */
  public static function compare(mixed $value, string $operator, mixed $compare): bool
  {
    $value = TypeHelper::toChar($value, true, true);
    $operator = TypeHelper::toString($operator, true, true);
    $compare = TypeHelper::toChar($compare, true, true);
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

  /**
   * Filter children by criteria like fieldname.eq.value
   * 
   * @TODO: Convert the different value-types (number, string, date) and make them comparable
   */
  public static function filterChildren(Page $page, array $filter): Pages
  {
    $children =  $page->children()->filter( // the Kirby-filter-function of children()
      function ($child) use ($filter) {
        $blueprint = BlueprintHelper::getBlueprint($child);
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
}
