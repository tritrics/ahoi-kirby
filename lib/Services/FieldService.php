<?php

namespace Tritrics\Api\Services;

use Kirby\Cms\Field as KirbyField;
use Tritrics\Api\Data\Collection;
use Tritrics\Api\Factories\ModelFactory;

/** */
class FieldService
{
  /**
   * Recoursive function to add field data to the given $resultObj object
   * includeFields only work for top-level fields, not for nested fields in structure or objects
   * 
   * @param (Collection) $resultObj Collection of the result data
   * @param (Fields) $allFields Kirby field object which contains the data
   * @param (Collection) $blueprintDef the blueprint definitions of the fields
   * @param (string) $lang two char language code
   * @param (int) $level interation count of recoursive
   * @param (array) $excludeFields explicit exclude fields from result data (for special case)
   * @return (void)
   */
  public static function addFields (
    Collection $resultObj,
    $allFields,
    $blueprintDef,
    $lang,
    string|array $fields = 'all'
  ) {

    $separator = kirby()->option('tritrics.restapi.field-name-separator');

    // loop blueprint definition
    foreach ($blueprintDef as $key => $fieldDef) {
      if ($fields !== 'all' && ! in_array($key, $fields)) {
        continue;
      }
      $field = isset($allFields[$key]) ? $allFields[$key] : new KirbyField(null, $key, '');
      $type = strtolower($fieldDef->node('type')->get());
      if (self::isHiddenField($fieldDef, $allFields)) {
        continue;
      }

      if (ModelFactory::has($type)) {
        if ($separator) {
          $key = explode($separator, $key);
        }
        $resultObj->add($key, ModelFactory::create($type, $field, $fieldDef, $lang));
      }
    }
  }

  /**
   * Creates a Kirby Field out of given values
   * @param (string) $type, can be any of Field-Classes of $models
   * @param (string) $key, the field name
   * @param (mixed) $value, the field value
   * @param (Collection) $blueprint, the field definition, can be null
   * @param (string) $lang
   * @return object
   */
  public static function factory ($type, $key, $value, $blueprint = null, $lang = null)
  {
    $model = kirby()->option('tritrics.restapi.models.' . $type);
    if ($model) {
      $kirbyField = new KirbyField(null, $key, $value);
      return new $model($kirbyField, $blueprint, $lang);
    }
  }

  /**
   * Check, if field is hidden in layout (conditional field).
   * Do not expose hidden fields, because they may contain data, which
   * is not intended by the user to be published. Also empty fields
   * pollute the result set.
   * @param (Collection) $blueprint
   * @param (array) $fields
   * @return bool
   */
  private static function isHiddenField($blueprint, $fields)
  {
    if ($blueprint->has('when')) {
      $conditions = $blueprint->node('when')->get();
      foreach ($conditions as $key => $value) {
        $key = strtolower($key);
        $condField = $fields[$key];
        if (isset($fields[$key])) {
          $condValue = GlobalService::typecastBool($condField->value(), null);
          if (is_bool($condValue)) {
            $value = GlobalService::typecastBool($value, null);
          } else {
            $value = GlobalService::typecast($value, true, true);
            $condValue = GlobalService::typecast($condField->value(), true, true);
          }
          if ($value !== $condValue) {
            return true;
          }
        }
      }
    }
    return false;
  }
}
