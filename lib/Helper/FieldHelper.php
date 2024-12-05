<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Cms\Field as KirbyField;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Factories\ModelFactory;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;

/**
 * Reads all Kirby fields of a blueprint and translates it to collection of models.
 */
class FieldHelper
{
  /**
   * Recoursive function to add field data to the given $resultObj object
   * includeFields only work for top-level fields, not for nested fields in structure or objects
   */
  public static function addFields(
    Collection $resultObj, //  Collection of the result data
    array $allFields,
    Collection $blueprint,
    ?string $lang,
    ?array $addFields = []
  ): void {
    $separator = ConfigHelper::get('field_name_separator', '');
    list($parent, $childs) = AccessHelper::splitFields($addFields);

    // loop blueprint definition
    foreach ($blueprint as $key => $blueprintField) {
      if (!AccessHelper::isAllowedField($key, $parent)) {
        continue;
      }
      $field = isset($allFields[$key]) ? $allFields[$key] : new KirbyField(null, $key, '');
      $type = strtolower($blueprintField->node('type')->get());
      if (self::isConditionalField($blueprintField, $allFields)) {
        continue;
      }

      if (ModelFactory::has($type)) {
        $childAddFields = isset($childs[$key]) ? $childs[$key] : [];
        if ($separator) {
          $key = explode($separator, $key);
        }
        $resultObj->add($key, ModelFactory::create(
          $type,
          $field,
          $blueprintField,
          $lang,
          $childAddFields
        ));
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
        if (isset($fields[$key])) {
          $condField = $fields[$key];
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
