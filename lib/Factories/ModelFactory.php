<?php

namespace Tritrics\Api\Factories;

/** */
class ModelFactory
{
  private static $map = [
    'blocks'      => 'Tritrics\Api\Fields\BlocksModel',
    'checkboxes'  => 'Tritrics\Api\Fields\OptionsModel',
    'date'        => 'Tritrics\Api\Fields\DatetimeModel',
    'email'       => 'Tritrics\Api\Fields\EmailModel',
    'files'       => 'Tritrics\Api\Fields\FilesModel',
    'hidden'      => 'Tritrics\Api\Fields\HiddenModel',
    'list'        => 'Tritrics\Api\Fields\TextModel',
    'multiselect' => 'Tritrics\Api\Fields\OptionsModel',
    'number'      => 'Tritrics\Api\Fields\NumberModel',
    'object'      => 'Tritrics\Api\Fields\ObjectModel',
    'pages'       => 'Tritrics\Api\Fields\PagesModel',
    'radio'       => 'Tritrics\Api\Fields\OptionModel',
    'range'       => 'Tritrics\Api\Fields\NumberModel',
    'select'      => 'Tritrics\Api\Fields\OptionModel',
    'slug'        => 'Tritrics\Api\Fields\TextModel',
    'structure'   => 'Tritrics\Api\Fields\StructureModel',
    'tags'        => 'Tritrics\Api\Fields\OptionsModel',
    'tel'         => 'Tritrics\Api\Fields\TelModel',
    'text'        => 'Tritrics\Api\Fields\TextModel',
    'textarea'    => 'Tritrics\Api\Fields\TextModel',
    'time'        => 'Tritrics\Api\Fields\DatetimeModel',
    'toggle'      => 'Tritrics\Api\Fields\BooleanModel',
    'toggles'     => 'Tritrics\Api\Fields\OptionModel',
    'url'         => 'Tritrics\Api\Fields\UrlModel',
    'users'       => 'Tritrics\Api\Fields\UsersModel',
    'writer'      => 'Tritrics\Api\Fields\TextModel'
  ];

  public static function hooks ()
  {
    kirby()->trigger('tritrics-api.register-model');
  }

  /** */
  public static function register ($type, $model)
  {
    if (is_string($type) && strlen($type) > 0 && class_exists($model)) {
      self::$map[$type] = $model;
    }
  }

  /** */
  public static function has ($type)
  {
    return isset(self::$map[$type]);
  }

  public static function get ($type)
  {
    if (self::has($type)) {
      return self::$map[$type];
    }
    return null;
  }

  /** */
  public static function create ($type, $field, $fieldDef, $lang)
  {
    $class = self::get($type);
    return new $class($field, $fieldDef, $lang);
  }
}