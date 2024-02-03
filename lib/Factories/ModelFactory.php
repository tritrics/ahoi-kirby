<?php

namespace Tritrics\AflevereApi\v1\Factories;

use Kirby\Content\Field;
use Kirby\Cms\Block;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;

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
  private static $classMap = [
    'block-default' => '\Models\BlockDefaultModel',
    'block-heading' => '\Models\BlockHeadingModel',
    'blocks'        => '\Models\BlocksModel',
    'checkboxes'    => '\Models\OptionsModel',
    'color'         => '\Models\ColorModel',
    'date'          => '\Models\DatetimeModel',
    'email'         => '\Models\EmailModel',
    'file'          => '\Models\FileModel',
    'files'         => '\Models\FilesModel',
    'hidden'        => '\Models\HiddenModel',
    'list'          => '\Models\TextModel',
    'link'          => '\Models\LinkModel',
    'multiselect'   => '\Models\OptionsModel',
    'number'        => '\Models\NumberModel',
    'object'        => '\Models\ObjectModel',
    'page'          => '\Models\PageModel',
    'pages'         => '\Models\PagesModel',
    'radio'         => '\Models\OptionModel',
    'range'         => '\Models\NumberModel',
    'select'        => '\Models\OptionModel',
    'slug'          => '\Models\TextModel',
    'structure'     => '\Models\StructureModel',
    'tags'          => '\Models\OptionsModel',
    'tel'           => '\Models\TelModel',
    'text'          => '\Models\TextModel',
    'textarea'      => '\Models\TextModel',
    'time'          => '\Models\DatetimeModel',
    'toggle'        => '\Models\BooleanModel',
    'toggles'       => '\Models\OptionModel',
    'url'           => '\Models\UrlModel',
    'users'         => '\Models\UsersModel',
    'writer'        => '\Models\TextModel',
  ];

  /**
   * Overwritten or added field type to model equivalents.
   * These can be added by other plugins with the hook
   * tritrics-aflevere-api-v1.register-model
   * 
   * @var array
   */
  private static $added = [];

  /**
   * Interface to trigger the hooks
   */
  public static function hooks (): void
  {
    kirby()->trigger('tritrics-aflevere-api-v1.register-model');
  }

  /**
   * Register a field type to model equivalent
   */
  public static function register (string $type, string $model): void
  {
    if (is_string($type) && strlen($type) > 0 && class_exists($model)) {
      self::$added[$type] = $model;
    }
  }

  /**
   * Check if a filed type is existing.
   */
  public static function has (string $type): bool
  {
    return $type === 'link' || isset(self::$added[$type]) || isset(self::$classMap[$type]);
  }

  /**
   * Create an instance of a model class defined by type.
   */
  public static function create (
    string $type,
    Field $field,
    Collection $def,
    ?string $lang
  ): object {
    $key = $type;
    if (isset(self::$added[$key])) {
      $class = self::$added[$key];
    } elseif (isset(self::$classMap[$key])) {
      $class = ConfigHelper::getNamespace() . self::$classMap[$key];
    } else {
      $class = ConfigHelper::getNamespace() . self::$classMap['text'];
    }
    return new $class($field, $def, $lang);
  }

  /**
   * Create an instance of a block-model class defined by type.
   */
  public static function createBlock(
    string $type,
    Block $block,
    Collection $def,
    ?string $lang
  ): object {
    $key = 'block-' . $type;
    if (isset(self::$added[$key])) {
      $class = self::$added[$key];
    } elseif (isset(self::$classMap[$key])) {
      $class = ConfigHelper::getNamespace() . self::$classMap[$key];
    } else {
      $class = ConfigHelper::getNamespace() . self::$classMap['block-default'];
    }
    return new $class($block, $def, $lang);
  }
}