<?php

namespace Tritrics\AflevereApi\v1\Factories;

use Tritrics\AflevereApi\v1\Services\ApiService;

/**
 * Translates Kirby's fields and objects to API data models.
 */
class ModelFactory
{
  /**
   * Kirby field type to model tranlation table.
   * 
   * @var Array
   */
  private static $buildIn = [
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
   * @var Array
   */
  private static $added = [];

  /**
   * Interface to trigger the hooks
   * 
   * @return Void 
   */
  public static function hooks ()
  {
    kirby()->trigger('tritrics-aflevere-api-v1.register-model');
  }

  /**
   * Register a field type to model equivalent
   * 
   * @param String $type 
   * @param String $model 
   * @return Void 
   */
  public static function register ($type, $model)
  {
    if (is_string($type) && strlen($type) > 0 && class_exists($model)) {
      self::$added[$type] = $model;
    }
  }

  /**
   * Check if a filed type is existing.
   * 
   * @param String $type 
   * @return Boolean 
   */
  public static function has ($type)
  {
    return $type === 'link' || isset(self::$added[$type]) || isset(self::$buildIn[$type]);
  }

  /**
   * Create an instance of a model class defined by type.
   * 
   * @param String $type 
   * @param Collection $field 
   * @param Collection $fieldDef 
   * @param String $lang 
   * @return Object 
   */
  public static function create ($type, $field, $fieldDef, $lang)
  {
    $key = $type;
    if (isset(self::$added[$key])) {
      $class = self::$added[$key];
    } elseif (isset(self::$buildIn[$key])) {
      $class = ApiService::$namespace . self::$buildIn[$key];
    } else {
      $class = ApiService::$namespace . self::$buildIn['text'];
    }
    return new $class($field, $fieldDef, $lang);
  }

  /**
   * Create an instance of a block-model class defined by type.
   * 
   * @param String $type 
   * @param Collection $field 
   * @param Collection $fieldDef 
   * @param String $lang 
   * @return Object 
   */
  public static function createBlock($type, $field, $fieldDef, $lang)
  {
    $key = 'block-' . $type;
    if (isset(self::$added[$key])) {
      $class = self::$added[$key];
    } elseif (isset(self::$buildIn[$key])) {
      $class = ApiService::$namespace . self::$buildIn[$key];
    } else {
      $class = ApiService::$namespace . self::$buildIn['block-default'];
    }
    return new $class($field, $fieldDef, $lang);
  }
}