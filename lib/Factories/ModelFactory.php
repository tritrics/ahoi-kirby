<?php

namespace Tritrics\Ahoi\v1\Factories;

use Kirby\Content\Field;
use Kirby\Cms\Block;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Data\EntriesModel;

/**
 * Translates Kirby's fields and objects to API data models.
 */
class ModelFactory
{
  /**
   * Kirby field type to model tranlation table.
   * 
   * @var array
   */
  protected static $classMap = [
    'blocks'      => 'BlocksModel',
    'checkboxes'  => 'OptionsModel',
    'color'       => 'ColorModel',
    'date'        => 'DatetimeModel',
    'email'       => 'EmailModel',
    'file'        => 'FileModel',
    'files'       => 'FilesModel',
    'hidden'      => 'HiddenModel',
    'list'        => 'TextModel',
    'link'        => 'LinkModel',
    'multiselect' => 'OptionsModel',
    'number'      => 'NumberModel',
    'object'      => 'ObjectModel',
    'page'        => 'PageModel',
    'pages'       => 'PagesModel',
    'radio'       => 'OptionModel',
    'range'       => 'NumberModel',
    'select'      => 'OptionModel',
    'slug'        => 'TextModel',
    'structure'   => 'StructureModel',
    'tags'        => 'OptionsModel',
    'tel'         => 'TelModel',
    'text'        => 'TextModel',
    'textarea'    => 'TextModel',
    'time'        => 'DatetimeModel',
    'toggle'      => 'BooleanModel',
    'toggles'     => 'OptionModel',
    'url'         => 'UrlModel',
    'user'        => 'UserModel',
    'users'       => 'UsersModel',
    'writer'      => 'TextModel',
  ];

  /**
   * Overwritten or added field type to model equivalents.
   * These can be added by other plugins with the hook
   * tritrics-ahoi-v1.register-model
   * 
   * @var array
   */
  protected static $added = [];

  /**
   * Create an instance of a model class defined by type.
   */
  public static function create(
    string $type,
    Field|Block $field,
    Collection $blueprint,
    string $lang = null,
    array $addFields = []
  ): object {
    $key = $type;

    // get model from added, definitions or default
    if (isset(self::$added[$key])) {
      $model = self::$added[$key];
    } elseif (isset(self::$classMap[$key])) {
      $model = ConfigHelper::getNamespace() . '\\Models\\' . self::$classMap[$key];
    } else {
      $model = ConfigHelper::getNamespace() . '\\Models\\' . self::$classMap['text'];
    }
    $instance = new $model($field, $blueprint, $lang, $addFields);
    if ($instance instanceof EntriesModel && $instance->isSingleEntry()) {
      $firstEntry = $instance->getFirstEntry();
      if (!$firstEntry) {
        $firstEntry = $instance->createEntry();
      }
      return $firstEntry;
    }
    return $instance;
  }

  /**
   * Check if a filed type is existing.
   */
  public static function has(string $type): bool
  {
    return $type === 'link' || isset(self::$added[$type]) || isset(self::$classMap[$type]);
  }

  /**
   * Interface to trigger the hooks
   */
  public static function hooks (): void
  {
    kirby()->trigger('tritrics-ahoi-v1.register-model');
  }

  /**
   * Register a field type to model equivalent
   */
  public static function register (string $type, array $def): void
  {
    if (is_string($type) && strlen($type) > 0 && class_exists($def['class'])) {
      self::$added[$type] = $def;
    }
  }
}