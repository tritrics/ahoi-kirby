<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Http\Route;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

class AccessHelper
{
  /**
   * Normalized settings from config.php
   */
  private static $blueprints = null;

  /**
   * Normalized settings from config.php
   */
  private static $routes = null;

  /**
   * Helper to normalize paths
   */
  private static function getPathNormalized(string $path): string
  {
    return trim(TypeHelper::toString($path, true, true), '/');
  }

  /**
   * Set access settings from config.php
   */
  private static function initBlueprints(): void
  {
    self::$blueprints = [
      '*' => false
    ];
    $config = ConfigHelper::get('blueprints');
    if (TypeHelper::isTrue($config)) {
      self::$blueprints['*'] = true;
    } else if (is_array($config)) {
      foreach ($config as $path => $access) {
        $name = self::getPathNormalized($path);
        if (strlen($name) > 0) {
          self::$blueprints[$name] = TypeHelper::toBool($access);
        }
      }
    }
  }

  /**
   * Set self::$routes to array with patterns from config.php.
   */
  private static function initRoutes(): void
  {
    $config = ConfigHelper::get('routes');
    $default = false;
    $patterns = [];
    if (TypeHelper::isTrue($config)) {
      $default = true;
    } else if (is_array($config)) {
      foreach($config as $pattern => $access) {
        if (TypeHelper::isBool($access)) {
          if ($pattern === '*') {
            $default = TypeHelper::toBool($access);
          } else {
            $pattern = self::getPathNormalized($pattern);
            $patterns[$pattern] = TypeHelper::toBool($access);
          }
        }
      }
    }
    self::$routes = [
      'default' => $default,
      'patterns' => []
    ];
    foreach ($patterns as $pattern => $access) {
      self::$routes['patterns'][] = new Route($pattern, 'ALL', function () {}, [ 'access' => $access ]);
    }
  }

  /**
   * Check if a given blueprint (by path) is private or allowed.
   */
  public static function isAllowedBlueprint(string $path): bool
  {
    if (self::$blueprints === null) {
      self::initBlueprints();
    }
    $name = self::getPathNormalized($path);
    return isset(self::$blueprints[$name]) ? self::$blueprints[$name] : self::$blueprints['*'];
  }

  /**
   * Check a model for both blueprint and route.
   */
  public static function isAllowedModel(Page|Site|File $model): bool
  {
    $res = self::isAllowedRoute($model);
    if ($res) {
      $res = self::isAllowedBlueprint(KirbyHelper::getBlueprintPath($model));
    }
    return $res;
  }

  /**
   * Check, if a given Page, Site or File is private or allowed.
   * Language slug is NOT checked, because it's easer to for the config
   * to define only the main slug. All pages are considered as one
   * regarding access rights.
   */
  public static function isAllowedRoute(Page|Site|File $model): bool
  {
    if (self::$routes === null) {
      self::initRoutes();
    }
    $uri = self::getPathNormalized(UrlHelper::getNode($model));
    $res = self::$routes['default'];
    foreach (self::$routes['patterns'] as $Route) {
      $match = $Route->parse($Route->pattern(), $uri);
      if ($match !== false) {
        $attr = $Route->attributes();
        $res = $attr['access'];
        break;
      }
    }
    return $res;
  }
}
