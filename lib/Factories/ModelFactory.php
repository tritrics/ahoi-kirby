<?php

namespace Tritrics\Api\Factories;

/** */
class ModelFactory
{
  private static $map = [
    'blocks'      => 'Tritrics\Api\Models\BlocksModel',
    'checkboxes'  => 'Tritrics\Api\Models\MultiselectModel',
    'date'        => 'Tritrics\Api\Models\DatetimeModel',
    'email'       => 'Tritrics\Api\Models\EmailModel',
    'files'       => 'Tritrics\Api\Models\FilesModel',
    'hidden'      => 'Tritrics\Api\Models\HiddenModel',
    'list'        => 'Tritrics\Api\Models\TextModel',
    'multiselect' => 'Tritrics\Api\Models\MultiselectModel',
    'number'      => 'Tritrics\Api\Models\NumberModel',
    'object'      => 'Tritrics\Api\Models\ObjectModel',
    'pages'       => 'Tritrics\Api\Models\PagesModel',
    'radio'       => 'Tritrics\Api\Models\SelectModel',
    'range'       => 'Tritrics\Api\Models\NumberModel',
    'select'      => 'Tritrics\Api\Models\SelectModel',
    'slug'        => 'Tritrics\Api\Models\TextModel',
    'structure'   => 'Tritrics\Api\Models\StructureModel',
    'tags'        => 'Tritrics\Api\Models\TagsModel',
    'tel'         => 'Tritrics\Api\Models\TelModel',
    'text'        => 'Tritrics\Api\Models\TextModel',
    'textarea'    => 'Tritrics\Api\Models\TextModel',
    'time'        => 'Tritrics\Api\Models\DatetimeModel',
    'toggle'      => 'Tritrics\Api\Models\ToggleModel',
    'toggles'     => 'Tritrics\Api\Models\SelectModel',
    'url'         => 'Tritrics\Api\Models\UrlModel',
    'users'       => 'Tritrics\Api\Models\UsersModel',
    'writer'      => 'Tritrics\Api\Models\TextModel'
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