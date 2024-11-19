<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Http\Route;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

class RouteAccessHelper
{
  /**
   * The default access, if not self::$routes match the uri.
   */
  private static $default = false;

  /**
   * An array with Routes.
   */
  private static $routes = null;

  /**
   * Check a single uri against self::$routes.
   */
  private static function getAccess($uri): bool
  {
    $res = self::$default;
    foreach(self::$routes as $Route) {
      $match = $Route->parse($Route->pattern(), $uri);
      if ($match !== false) {
        $attr = $Route->attributes();
        $res = $attr['access'];
        break;
      }
    }
    return $res;
  }

  /**
   * Set self::$routes to array with patterns from config.php.
   */
  private static function initRoutes(): void
  {
    $config = ConfigHelper::get('routes');
    $routes = [];
    if (TypeHelper::isTrue($config)) {
      self::$default = true;
    } else if (is_array($config)) {
      foreach($config as $pattern => $access) {
        if (TypeHelper::isBool($access)) {
          if ($pattern === '*') {
            self::$default = TypeHelper::toBool($access);
          } else {
            $pattern = trim($pattern, '/'); // intern we work without leading slashes
            $routes[$pattern] = TypeHelper::toBool($access);
          }
        }
      }
    }
    self::$routes = [];
    foreach ($routes as $pattern => $access) {
      self::$routes[] = new Route($pattern, 'ALL', function () {}, [ 'access' => $access ]);
    }
  }

  /**
   * Check, if a given Page, Site or File is private or allowed.
   * Language slug is NOT checked, because it's easer to for the config
   * to define onl the main slug. All pages are considered as one
   * regarding access rights.
   */
  public static function isAllowed(Page|Site|File $node): bool
  {
    if (self::$routes === null) {
      self::initRoutes();
    }
    $uri = trim(UrlHelper::getNode($node), '/');  // intern we work without leading slashes
    return self::getAccess($uri);
  }
}
