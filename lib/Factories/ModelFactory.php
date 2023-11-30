<?php

namespace Tritrics\AflevereApi\v1\Factories;

use Tritrics\AflevereApi\v1\Services\ApiService;

/** */
class ModelFactory
{
  private static $buildIn = [
    'blocks'      => '\Fields\BlocksModel',
    'checkboxes'  => '\Fields\OptionsModel',
    'date'        => '\Fields\DatetimeModel',
    'email'       => '\Fields\EmailModel',
    'files'       => '\Fields\FilesModel',
    'hidden'      => '\Fields\HiddenModel',
    'list'        => '\Fields\TextModel',
    'multiselect' => '\Fields\OptionsModel',
    'number'      => '\Fields\NumberModel',
    'object'      => '\Fields\ObjectModel',
    'pages'       => '\Fields\PagesModel',
    'radio'       => '\Fields\OptionModel',
    'range'       => '\Fields\NumberModel',
    'select'      => '\Fields\OptionModel',
    'slug'        => '\Fields\TextModel',
    'structure'   => '\Fields\StructureModel',
    'tags'        => '\Fields\OptionsModel',
    'tel'         => '\Fields\TelModel',
    'text'        => '\Fields\TextModel',
    'textarea'    => '\Fields\TextModel',
    'time'        => '\Fields\DatetimeModel',
    'toggle'      => '\Fields\BooleanModel',
    'toggles'     => '\Fields\OptionModel',
    'url'         => '\Fields\UrlModel',
    'users'       => '\Fields\UsersModel',
    'writer'      => '\Fields\TextModel'
  ];

  private static $added = [];

  public static function hooks ()
  {
    kirby()->trigger('tritrics-aflevere-api-v1.register-model');
  }

  /** */
  public static function register ($type, $model)
  {
    if (is_string($type) && strlen($type) > 0 && class_exists($model)) {
      self::$added[$type] = $model;
    }
  }

  /** */
  public static function has ($type)
  {
    return isset(self::$added[$type]) || isset(self::$buildIn[$type]);
  }

  public static function get ($type)
  {
    if (isset(self::$added[$type])) {
      return self::$added[$type];
    } else if (isset(self::$buildIn[$type])) {
      return ApiService::$namespace . self::$buildIn[$type];
    }
    return ApiService::$namespace . self::$buildIn['text'];
  }

  /** */
  public static function create ($type, $field, $fieldDef, $lang)
  {
    $class = self::get($type);
    return new $class($field, $fieldDef, $lang);
  }
}