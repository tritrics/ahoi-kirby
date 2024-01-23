<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Cms\Field as KirbyField;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Factories\ModelFactory;
use Tritrics\AflevereApi\v1\Services\ApiService;

/**
 * Reads all Kirby fields of a blueprint and translates it to collection of models.
 */
class FieldService
{
  /**
   * Recoursive function to add field data to the given $resultObj object
   * includeFields only work for top-level fields, not for nested fields in structure or objects
   * 
   * @param Collection $resultObj Collection of the result data
   * @param Fields $allFields Kirby field object which contains the data
   * @param Collection $blueprint the blueprint definitions of the fields
   * @param String $lang two char language code
   * @param Integer $level interation count of recoursive
   * @param Array $excludeFields explicit exclude fields from result data (for special case)
   * @return Void
   */
  public static function addFields (
    Collection $resultObj,
    $allFields,
    $blueprint,
    $lang,
    string|array $fields = 'all'
  ) {

    $separator = ApiService::getconfig('field-name-separator', '');

    // loop blueprint definition
    foreach ($blueprint as $key => $blueprintField) {
      if ($fields !== 'all' && ! in_array($key, $fields)) {
        continue;
      }
      $field = isset($allFields[$key]) ? $allFields[$key] : new KirbyField(null, $key, '');
      $type = strtolower($blueprintField->node('type')->get());
      if (self::isConditionalField($blueprintField, $allFields)) {
        continue;
      }

      if (ModelFactory::has($type)) {
        if ($separator) {
          $key = explode($separator, $key);
        }
        $resultObj->add($key, ModelFactory::create($type, $field, $blueprintField, $lang));
      }
    }
  }

  /**
   * Creates a Kirby Field out of given values.
   * 
   * @param String $type can be any of Field-Classes of $models
   * @param String $key the field name
   * @param Mixed $value the field value
   * @param Collection $blueprint the field definition, can be null
   * @param String $lang the 2-digit language code
   * @return Object|Void 
   */
  public static function factory ($type, $key, $value, $blueprint = null, $lang = null)
  {
    $model = ApiService::getconfig('models.' . $type);
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
   * 
   * @param Collection $blueprint 
   * @param Array $fields 
   * @return Boolean 
   */
  private static function isConditionalField($blueprint, $fields)
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
