<?php

namespace Tritrics\Ahoi\v1\Helper;

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
    $slug = trim(trim(self::getConfig('slug', ''), '/'));
    if (is_string($slug) && strlen($slug) > 0) {
      $slug = '/' . $slug;
    }
    return $slug . '/' . self::$globals['version'];
  }

  /**
   * Get setting from plugins config.php
   * example: tritrics.ahoi.v1.slug
   */
  public static function getConfig(string $node, mixed $default = null): mixed
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
    return kirby()->plugin(self::$globals['plugin-name'])->version();
  }

  /**
   * Get the API version
   */
  public static function getVersion(): string
  {
    return self::$globals['version'];
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
  private static function isEnabled(string $method): bool
  {
    $global = self::getConfig('enabled', false);
    $setting = self::getConfig('enabled.' . $method, false);
    return $global === true || $setting === true;
  }

  /**
   * Check if "form" action is enabled.
   */
  public static function isEnabledAction(): bool
  {
    return self::isEnabled('action');
  }

  /**
   * Check if "info" action is enabled.
   */
  public static function isEnabledInfo(): bool
  {
    return self::isEnabled('info');
  }

  /**
   * Check if "language" action is enabled.
   */
  public static function isEnabledLanguage(): bool
  {
    return self::isEnabled('language');
  }

  /**
   * Check if "page" action is enabled.
   */
  public static function isEnabledFile(): bool
  {
    return self::isEnabled('file');
  }

  /**
   * Check if "pages" action is enabled.
   */
  public static function isEnabledFiles(): bool
  {
    return self::isEnabled('files');
  }

  /**
   * Check if "page" action is enabled.
   */
  public static function isEnabledPage(): bool
  {
    return self::isEnabled('page');
  }

  /**
   * Check if "pages" action is enabled.
   */
  public static function isEnabledPages(): bool
  {
    return self::isEnabled('pages');
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
   * Check, if a slug the backend-user enters, has a conflict with the API-Route
   */
  public static function isProtectedSlug(string $slug): bool
  {
    $path = strtolower(self::getConfig('slug'));
    $slugs = explode('/', $path);
    return in_array(strtolower($slug), $slugs);
  }
}
