<?php

namespace Tritrics\AflevereApi\v1\Factories;

use Tritrics\AflevereApi\v1\Services\ApiService;

/** */
class ModelFactory
{
  private static $buildIn = [
    'blocks'      => '\Models\BlocksModel',
    'checkboxes'  => '\Models\OptionsModel',
    'date'        => '\Models\DatetimeModel',
    'email'       => '\Models\EmailModel',
    'files'       => '\Models\FilesModel',
    'hidden'      => '\Models\HiddenModel',
    'list'        => '\Models\TextModel',
    'multiselect' => '\Models\OptionsModel',
    'number'      => '\Models\NumberModel',
    'object'      => '\Models\ObjectModel',
    'pages'       => '\Models\NodesModel',
    'radio'       => '\Models\OptionModel',
    'range'       => '\Models\NumberModel',
    'select'      => '\Models\OptionModel',
    'slug'        => '\Models\TextModel',
    'structure'   => '\Models\StructureModel',
    'tags'        => '\Models\OptionsModel',
    'tel'         => '\Models\TelModel',
    'text'        => '\Models\TextModel',
    'textarea'    => '\Models\TextModel',
    'time'        => '\Models\DatetimeModel',
    'toggle'      => '\Models\BooleanModel',
    'toggles'     => '\Models\OptionModel',
    'url'         => '\Models\UrlModel',
    'users'       => '\Models\UsersModel',
    'writer'      => '\Models\TextModel'
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