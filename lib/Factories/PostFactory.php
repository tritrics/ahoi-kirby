<?php

namespace Tritrics\Ahoi\v1\Factories;

use Exception;
use Kirby\Cms\Page;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;
use Tritrics\Ahoi\v1\Helper\UrlHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Helper to translate form data into Kirby page model.
 */
class PostFactory
{
  /**
   * List of Kirby field type which can be used in forms
   * with their corresponding native data type.
   * Not listed types: pseudo fields or unhandled data structure.
   */
  public static $validFieldTypes = [
    'checkboxes'  => 'string[]',
    'color'       => 'string',
    'date'        => 'string',
    'email'       => 'string',
    'link'        => 'string',
    'multiselect' => 'string[]',
    'number'      => 'number',
    'radio'       => 'string',
    'range'       => 'number',
    'select'      => 'string',
    'slug'        => 'string',
    'tags'        => 'string[]',
    'tel'         => 'string',
    'text'        => 'string',
    'textarea'    => 'text',
    'time'        => 'string',
    'toggle'      => 'bool',
    'toggles'     => 'string',
    'url'         => 'string',
    'writer'      => 'text',
  ];

  /**
   * Creates as Page object from post values.
   */
  public static function create (string $lang, string $action, array $data): Page
  {
    $uuid = Str::lower(Str::random(16, 'base32hex'));

    // Template
    $template = ConfigHelper::getConfig('actions.' . $action . '.template', '');
    $file = rtrim(kirby()->root('blueprints'), '/') . '/pages/' . $template . '.yml';
    if (!F::exists($file)) {
      throw new Exception('Template configuration is missing or wrong in config.php.', 16); // @errno16
    };

    // Parent, ignored needed if page is not saved
    $parent = false;
    if (ConfigHelper::getConfig('actions.' . $action . '.save', true)) {
      $parent = KirbyHelper::findPage($lang, ConfigHelper::getConfig('actions.' . $action . '.parent', null));
      if (!$parent instanceof Page) {
        throw new Exception('Parent configuration is missing or wrong in config.php.', 17); // @errno17
      }
    }

    // get Content
    $stripTags = ConfigHelper::getConfig('form_security.strip_tags', true);
    $stripBackslashes = ConfigHelper::getConfig('form_security.strip_backslashes', true);
    $content = [
      'title' => ConfigHelper::getConfig('actions.' . $action . '.title', 'Incoming %created (action %action)'),
      'uuid' => $uuid,
    ];
    foreach (self::fields($action) as $key => $type) {
      switch ($key) {
        case 'title':
        case 'uuid':
          continue 2; // don't allow overwrite
        case 'action':
          $content[$key] = $action;
          break;
        case 'created':
          $content[$key] = date('Y-m-d H:i:s');
          break;
        case 'host':
          $content[$key] = UrlHelper::getReferer();
          break;
        case 'ip':
          $content[$key] = UrlHelper::getClientIp();
          break;
        case 'lang':
          $content[$key] = $lang;
          break;
        default:
          $content[$key] = self::sanitize($data[$key] ?? '', $type, $stripTags, $stripBackslashes);
      }
      $content['title'] = str_replace('%' . $key, (string) $content[$key], $content['title']);
    }

    // create Page
    $config = [
      'slug' => $uuid,
      'template' => $template,
      'content' => $content,
      'isDraft' => true,
    ];
    if ($parent) {
      $config['parent'] = $parent;
    }
    return KirbyHelper::createPage($config);
  }

  /**
   * Get a list of field names from template, which have a proper type.
   */
  public static function fields(string $action): array
  {
    // create a dummy page to get blueprint-fields
    $template = ConfigHelper::getConfig('actions.' . $action . '.template', '');
    $dummy = new Page(['slug' => 'dummy', 'template' => $template]);
    $res = [];
    foreach ($dummy->blueprint()->fields() as $key => $def) {
      $type = $def['type'];
      if (isset(self::$validFieldTypes[$type])) {
        $res[$key] = TypeHelper::toString($type, true, true);
      }
    }
    return $res;
  }

  /**
   * Sanitize input values.
   */
  private static function sanitize(
    mixed $value,
    string $type,
    bool $stripTags,
    bool $stripBackslashes
  ): string|int|float|null {
    if (isset(self::$validFieldTypes[$type])) {
      switch (self::$validFieldTypes[$type]) {
        case 'string[]':
          if (!is_array($value)) {
            $value = [ $value ];
          }
          $res = array_map(function ($option) use ($stripTags, $stripBackslashes) {
            return self::sanitizeString($option, $stripTags, $stripBackslashes, true);
          }, $value);
          return implode(', ', $res);
        case 'number':
          return self::sanitizeNumber($value);
        case 'string':
          return self::sanitizeString($value, $stripTags, $stripBackslashes, true);
        case 'text':
          return self::sanitizeString($value, $stripTags, $stripBackslashes, false);
        case 'bool':
          return self::sanitizeBool($value);
      }
    }
    return null;
  }

  /**
   * Converts 1, true, "true", 0, false, "false" to boolean.
   */
  private static function sanitizeBool (mixed $value): bool|null
  {
    if (TypeHelper::isBool($value)) { // value is 1 | 0 | null
      return TypeHelper::toBool($value); // Kirby works with boolean or string here
    }
    return null;
  }

  /**
   * Converts numbers with data type string to integer or float.
   */
  private static function sanitizeNumber (mixed $value): int|float|null
  {
    if (TypeHelper::isNumber($value)) {
      return TypeHelper::toNumber($value);
    }
    return null;
  }

  /**
   * Converts to string and does some security sanitizations.
   */
  private static function sanitizeString (
    mixed $value,
    bool $stripTags,
    bool $stripBackslashes,
    bool $stripNL
  ): string|null {
    if (TypeHelper::isString($value)) {
      $res = TypeHelper::toString($value, true, false);
      $res = $stripNL ? preg_replace('/\s+/', ' ', $res) : $res;
      $res = $stripTags ? strip_tags($res) : $res;
      return $stripBackslashes ? stripslashes($res) : $res;
    }
    return null;
  }
}
