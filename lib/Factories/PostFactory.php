<?php

namespace Tritrics\AflevereApi\v1\Factories;

use Exception;
use Kirby\Cms\Page;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;
use Tritrics\AflevereApi\v1\Helper\KirbyHelper;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\TypeHelper;

/**
 * Helper to translate form data into Kirby page model.
 */
class PostFactory
{
  /**
   * commented types: @todo
   * not listed types: unhandled data structure
   */
  private static $validFieldTypes = [
    //'checkboxes',
    'color',
    'date',
    'email',
    'hidden',
    'link',
    //'multiselect',
    'number',
    //'radio',
    'range',
    //'select',
    'slug',
    //'tags',
    'tel',
    'text',
    'textarea',
    'time',
    //'toggle',
    //'toggles',
    'url',
    'writer',
  ];

  /**
   * Creates as Page object from post values.
   */
  public static function create (string $lang, string $action, array $data): Page
  {
    $uuid = Str::lower(Str::random(16, 'base32hex'));
    $hosts = RequestHelper::getHosts();

    // Template
    $template = ConfigHelper::getConfig('actions.' . $action . '.template', '');
    $file = rtrim(kirby()->root('blueprints'), '/') . '/pages/' . $template . '.yml';
    if (!F::exists($file)) {
      throw new Exception('Template configuration is missing or wrong in config.php.', 17); // @errno17
    };

    // Parent, ignored needed if page is not saved
    $parent = false;
    if (ConfigHelper::getConfig('actions.' . $action . '.save', true)) {
      $parent = KirbyHelper::findPage(ConfigHelper::getConfig('actions.' . $action . '.parent', null));
      if (!$parent instanceof Page) {
        throw new Exception('Parent configuration is missing or wrong in config.php.', 17); // @errno17
      }
    }

    // get Content
    $stripTags = ConfigHelper::getConfig('form-security.strip-tags', true);
    $stripBackslashes = ConfigHelper::getConfig('form-security.strip-backslashes', true);
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
          $content[$key] = $hosts['referer']['host'];
          break;
        case 'ip':
          $content[$key] = $hosts['referer']['ip'];
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
  private static function fields(string $action): array
  {
    // create a dummy page to get blueprint-fields
    $template = ConfigHelper::getConfig('actions.' . $action . '.template', '');
    $dummy = new Page(['slug' => 'dummy', 'template' => $template]);
    $res = [];
    foreach ($dummy->blueprint()->fields() as $key => $def) {
      if (in_array($def['type'], self::$validFieldTypes) && !in_array($key, $res)) {
        $res[$key] = TypeHelper::toString($def['type'], true, true);
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

    // Numbers
    if (TypeHelper::isNumber($value)) {
      return TypeHelper::toNumber($value);
    }

    // Strings
    if (TypeHelper::isString($value)) {
      $res = TypeHelper::toString($value, true, false);

      // remove linebreaks in none-multiline strings
      if ($type !== 'textarea' || $type !== 'writer') {
        $res = preg_replace('/\s+/', ' ', $res);
      }
      if ($stripTags) {
        $res = strip_tags($res);
      }
      if ($stripBackslashes) {
        $res = stripslashes($res);
      }
      return $res;
    }
    return null;
  }
}
