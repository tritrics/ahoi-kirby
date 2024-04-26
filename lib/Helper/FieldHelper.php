<?php

namespace Tritrics\Tric\v1\Helper;

use Kirby\Cms\Field as KirbyField;
use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Factories\ModelFactory;
use Tritrics\Tric\v1\Helper\ConfigHelper;

/**
 * Reads all Kirby fields of a blueprint and translates it to collection of models.
 */
class FieldHelper
{
  /**
   * Recoursive function to add field data to the given $resultObj object
   * includeFields only work for top-level fields, not for nested fields in structure or objects
   */
  public static function addFields (
    Collection $resultObj, //  Collection of the result data
    array $allFields,
    Collection $blueprint,
    ?string $lang,
    string|array $fields = 'all'
  ): void {
    $separator = ConfigHelper::getconfig('field-name-separator', '');

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
   * Check, if field is hidden in layout (conditional field).
   * Do not expose hidden fields, because they may contain data, which
   * is not intended by the user to be published. Also empty fields
   * pollute the result set.
   */
  private static function isConditionalField(Collection $blueprint, array $fields): bool
  {
    if ($blueprint->has('when')) {
      $conditions = $blueprint->node('when')->get();
      foreach ($conditions as $key => $value) {
        $key = strtolower($key);
        $condField = $fields[$key];
        if (isset($fields[$key])) {
          $condValue =
            TypeHelper::isBool($condField->value())
              ? TypeHelper::toBool($condField->value())
              : null;
          if (is_bool($condValue)) {
            $value =
              TypeHelper::isBool($value)
                ? TypeHelper::toBool($value)
                : null;
          } else {
            $value = TypeHelper::toChar($value, true, true);
            $condValue = TypeHelper::toChar($condField->value(), true, true);
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
