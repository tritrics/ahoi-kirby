<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Exception\Exception;
use Kirby\Exception\DuplicateException;

/**
 * Functions to read and check configuration.
 */
class ConfigHelper
{
  /**
   * Global vars
   * 
   * @var array
   */
  private static $globals = [];

  /**
   * Compute the base slug like /public-api/v1 
   */
  public static function getApiSlug(): string
  {
    $slug = trim(trim(self::get('slug', ''), '/'));
    if (is_string($slug) && strlen($slug) > 0) {
      $slug = '/' . $slug;
    }
    return $slug . '/' . self::$globals['version'];
  }

  /**
   * Get setting from plugins config.php
   * example: tritrics.ahoi.v1.slug
   */
  public static function get(string $node, mixed $default = null): mixed
  {
    $val = kirby()->option(str_replace('/', '.', self::$globals['plugin-name']) . '.' . $node, $default);
    if ($default !== null && gettype($val) !== gettype($default)) {
      return $default;
    }
    return $val;
  }

  /**
   * Get the namespace for dynamic imports
   */
  public static function getNamespace(): string
  {
    return self::$globals['namespace'];
  }

  /**
   * Get the version from composer.json
   */
  public static function getPluginName(): string
  {
    return self::$globals['plugin-name'];
  }

  /**
   * Get the version from composer.json
   * 
   * @throws DuplicateException 
   */
  public static function getPluginVersion(): string
  {
    $version = kirby()->plugin(self::$globals['plugin-name'])->version();
    return $version ?? 'unkown';
  }

  /**
   * Get the API version
   */
  public static function getVersion(): string
  {
    return self::$globals['version'];
  }

  /**
   * Check, if actions are defined in config.
   */
  public static function hasActions(): bool
  {
    $actions = self::get('actions');
    return is_array($actions) && count($actions) > 0;
  }

  /**
   * Doing some initialization stuff.
   */
  public static function init($globals): void
  {
    self::$globals = $globals;
  }

  /**
   * Check, if API's functions are enabled.
   */
  public static function isEnabled(): bool
  {
    return self::get('enabled', false);
  }

  /**
   * Check if installation is multilang.
   * Multilang-site is defined in config.php: languages => true.
   */
  public static function isMultilang(): bool
  {
    // kirby()->multilang() is not working correctly.
    // It's returning true, even if config is false.
    return kirby()->option('languages', false) === true;
  }

  /**
   * Check, if a slug has a conflict with not-allowed slugs.
   * Because it's not possible to hook a page-move, the not-allowed slugs must be prevented
   * in every part of the uri, not just at the beginning.
   * @throws Exception
   */
  public static function checkSlug($path): void
  {
    $uri = '/' . trim($path, '/') . '/';
    $prevent = explode('/', strtolower(self::get('slug')));
    if (self::isMultilang()) {
      foreach(LanguagesHelper::getAll() as $language) {
        $prevent = array_merge($prevent, [ $language->code() ], UrlHelper::getSlugs($language->url()));
      }
    }
    $prevent = array_map(function ($elem) {
      return '/' . trim($elem, '/') . '/';
    }, array_unique(array_filter($prevent)));
    foreach($prevent as $slug) {
      if (strpos($uri, $slug) !== false) {
        throw new Exception('Ahoi-Plugin: Slug "' . trim($slug, '/') . '" not allowed');
      }
    }
  }
}
