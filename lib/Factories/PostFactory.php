<?php

namespace Tritrics\AflevereApi\v1\Factories;

use Exception;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\TypeHelper;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;
use Tritrics\AflevereApi\v1\Post\PostValues;

/**
 * Loop input fields definitions for a given action, collect the
 * values from postdata and create instances by value type.
 */
class PostFactory
{
  /**
   * Class map
   * 
   * @var array
   */
   private static $classMap = [
    'base'    => '\Post\BaseValue',   // generic, no special data type
    'string'  => '\Post\StringValue', // without linebreaks
    'text'    => '\Post\TextValue',   // with linebreaks
    'number'  => '\Post\NumberValue', // integers or floats
    'email'   => '\Post\EmailValue',
    'url'     => '\Post\UrlValue',
    'bool'    => '\Post\BoolValue',   // 0, 1, '0', '1', 'false', 'true', converts to bool
  ];

  /**
   * Create instance of PostValues as a collection of input values.
   * 
   * @throws Exception 
   */
  public static function create (string $action, array $postData): PostValues
  {
    $def = self::readDef($action);
    return self::createInstances($postData, $def);
  }

  /**
   * Compute separate object with meta values.
   */
  public static function createMeta(?string $lang): PostValues
  {
    $hosts = RequestHelper::getHosts($lang);
    $class = ConfigHelper::getNamespace() . self::$classMap['base'];
    return new PostValues([
      'date' => new $class(date('Y-m-d')),
      'time' => new $class(date('H:i:s')),
      'host' => new $class($hosts['referer']['host']),
      'ip'   => new $class($hosts['referer']['ip']),
      'lang' => new $class($lang),
    ]);
  }

  /**
   * Read the definition of the inputs from actions in config.php.
   *
   * @throws Exception 
   */
  private static function readDef (string $action): array
  {
    $action = TypeHelper::toString($action, true, true);
    if (strlen($action)) {
      $def = ConfigHelper::getConfig('actions.' . $action . '.input');
    }
    if (!is_array($def)) {
      throw new Exception('Post-input configuration is missing or incomplete in config.php.', 17); // @errno17
    }
    return $def;
  }

  /**
   * Take those fields from $post to $fields, that are defined in $def
   * and skip all the rest.
   */
  private static function createInstances (array $data, array $fieldsDef): PostValues
  {
    // sanitize input names, collect valid inputs
    $inputs = [];
    foreach ($data as $key => $value) {
      $key = TypeHelper::toString($key, true, true);
      if (self::isValidInput($key, $fieldsDef)) {
        $inputs[$key] = $value;
      }
    }

    // create values classes for every field in def and pass post-value
    $instances = [];
    foreach ($fieldsDef as $key => $def) {
      $type = $def['type'];
      $value = isset($inputs[$key]) ? $inputs[$key] : null;
      if (substr($type, -2) === '[]') {
        // missing: array-types like string[], number[]...
      } else {
        $class = ConfigHelper::getNamespace() . self::$classMap[$type];
        $instances[$key] = new $class($value, $def);
      }
    }
    return new PostValues($instances);
  }

  /**
   * Check if a field (key in post-data) is defined.
   */
  private static function isValidInput(string $key, array $def): bool
  {
    if (
      !isset($def[$key]) ||
      !is_array($def[$key]) ||
      !isset($def[$key]['type']) ||
      !is_string($def[$key]['type'])
    ) {
      return false;
    }
    return isset(self::$classMap[$def[$key]['type']]);
  }
}