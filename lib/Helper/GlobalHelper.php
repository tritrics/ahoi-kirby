<?php

namespace Tritrics\AflevereApi\v1\Helper;

use Kirby\Cms\Response;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Kirby\Exception\DuplicateException;

/**
 * Collection of globally used functions.
 */
class GlobalHelper
{
  /**
   * Global vars
   * 
   * @var Array
   */
  private static $globals = [];

  /**
   * Backend and frontend host infos.
   * 
   * @var array
   */
  private static $hosts = [];

  /**
   * Doing some initialization stuff.
   * 
   * @return void 
   */
  public static function init($globals)
  {
    self::$globals = $globals;
  }

  /**
   * Get the version from composer.json
   * 
   * @return String 
   * @throws DuplicateException 
   */
  public static function getPluginVersion ()
  {
    return kirby()->plugin(self::$globals['plugin-name'])->version();
  }

  /**
   * Get the API version
   * 
   * @return String
   */
  public static function getVersion()
  {
    return self::$globals['version'];
  }

  /**
   * Get the version from composer.json
   * 
   * @return String 
   */
  public static function getPluginName()
  {
    return self::$globals['plugin-name'];
  }

  /**
   * Get the namespace for dynamic imports
   * 
   * @return String
   */
  public static function getNamespace()
  {
    return self::$globals['namespace'];
  }

  /**
   * Get setting from plugins config.php
   * example: tritrics.aflevere-api.v1.slug
   * 
   * @param String $node 
   * @param Mixed $default 
   * @return Mixed 
   */
  public static function getConfig($node, $default = null)
  {
    $val = kirby()->option(str_replace('/', '.', self::$globals['plugin-name']) . '.' . $node, $default);
    if ($default !== null && gettype($val) !== gettype($default)) {
      return $default;
    }
    return $val;
  }

  /**
   * Compute the base slug like /public-api/v1
   * 
   * @return null|string 
   */
  public static function getApiSlug()
  {
    $slug = trim(trim(self::getConfig('slug', ''), '/'));
    if (is_string($slug) && strlen($slug) > 0) {
      $slug = '/' . $slug;
    }
    return $slug . '/' . self::$globals['version'];
  }

  /**
   * Check, if a slug the backend-user enters, has a conflict with the API-Route
   * 
   * @param Mixed $slug 
   * @return Boolean 
   */
  public static function isProtectedSlug($slug)
  {
    $path = strtolower(self::getConfig('slug'));
    $slugs = explode('/', $path);
    return in_array(strtolower($slug), $slugs);
  }

  /**
   * Parse the given path and return language and node. In a multi language
   * installation, the first part of the path must be a valid language (which
   * is not validated here).
   * 
   * single language installation:
   * "/" -> site
   * "/some/page" -> page
   * 
   * multi language installation:
   * "/en" -> english version of site
   * "/en/some/page" -> english version of page "/some/path"
   * 
   * @param Mixed $path 
   * @param Boolean $multilang
   * @return Array 
   */
  public static function parsePath($path, $multilang)
  {
    $parts = array_filter(explode('/', $path));
    $lang = $multilang ? array_shift($parts) : null;
    $slug = count($parts) > 0 ? implode('/', $parts) : null;
    return [$lang, $slug];
  }

  /**
   * Similar to parsePath()
   * 
   * @param mixed $path 
   * @param mixed $multilang 
   * @return (string|null)[] 
   */
  public static function parseAction($path, $multilang)
  {
    $parts = array_filter(explode('/', $path));
    $lang = $multilang ? array_shift($parts) : null;
    $action = array_shift($parts);
    $token = count($parts) > 0 ? array_shift($parts) : null;
    return [$lang, $action, $token];
  }

  /**
   * Check if "info" action is enabled.
   * 
   * @return Boolean 
   */
  public static function isEnabledInfo()
  {
    return self::isEnabled('info');
  }

  /**
   * Check if "language" action is enabled.
   * 
   * @return Boolean 
   */
  public static function isEnabledLanguage()
  {
    return self::isEnabled('language');
  }

  /**
   * Check if "page" action is enabled.
   * 
   * @return Boolean 
   */
  public static function isEnabledPage()
  {
    return self::isEnabled('page');
  }

  /**
   * Check if "pages" action is enabled.
   * 
   * @return Boolean 
   */
  public static function isEnabledPages()
  {
    return self::isEnabled('pages');
  }

  /**
   * Check if "form" action is enabled.
   * 
   * @return Boolean 
   */
  public static function isEnabledAction()
  {
    return self::isEnabled('action');
  }

  /**
   * Helper: Find a page by translated slug
   * (Kirby can only find by default slug)
   * 
   * @param Mixed $lang 
   * @param Mixed $slug 
   * @return Mixed 
   */
  public static function findPageBySlug($lang, $slug)
  {
    if (LanguagesService::isMultilang()) {
      $pages = kirby()->site()->pages();
      $keys = explode('/', trim($slug, '/'));
      return self::findPageBySlugRec($pages, $lang, $keys);
    } else {
      return page($slug);
    }
  }

  /**
   * Init response with basic properties.
   * 
   * @param Integer $status 
   * @param String $msg 
   * @return Collection 
   */
  public static function initResponse($status = 200, $msg = 'OK')
  {
    $Request = kirby()->request();
    $res = new Collection();
    $res->add('ok', $status === 200);
    $res->add('status', $status);
    $res->add('msg', $msg);
    $res->add('url', $Request->url()->toString());
    return $res;
  }

  /**
   * Response: OK
   * 
   * @param String $msg 
   * @return Response 
   */
  public static function ok($msg = 'OK')
  {
    return Response::json(self::initResponse(200, $msg)->get(), 200);
  }

  /**
   * Reponse: Invalid language.
   * 
   * @return Response 
   */
  public static function invalidLang()
  {
    return self::badRequest('Given language is not valid');
  }

  /**
   * Response: Bad Request.
   * 
   * @param String $msg 
   * @return Response 
   */
  public static function badRequest($msg = 'Bad Request')
  {
    return Response::json(self::initResponse(400, $msg)->get(), 400);
  }

  /**
   * Response: API is diabled.
   * 
   * @param String $msg 
   * @return Response 
   */
  public static function disabled($msg = 'API is disabled for this action')
  {
    return Response::json(self::initResponse(403, $msg)->get(), 403);
  }

  /**
   * Response: Not found.
   * 
   * @param String $msg 
   * @return Response 
   */
  public static function notFound($msg = 'Page is not found')
  {
    return Response::json(self::initResponse(404, $msg)->get(), 404);
  }

  /**
   * Response: Not Allowed.
   * 
   * @param String $msg 
   * @return Response 
   */
  public static function notAllowed($msg = 'Action not allowed')
  {
    return Response::json(self::initResponse(405, $msg)->get(), 405);
  }

  /**
   * Response: Internal Server Error.
   * 
   * @param String $msg 
   * @return Response 
   */
  public static function fatal($msg = 'Internal Server Error')
  {
    return Response::json(self::initResponse(500, $msg)->get(), 500);
  }

  /**
   * Response: Not implemented.
   * 
   * @param String $msg 
   * @return Response 
   */
  public static function notimplemented($msg = 'Not Implemented or misconfigured')
  {
    return Response::json(self::initResponse(501, $msg)->get(), 501);
  }

  /**
   * Check, if API's functions are enabled.
   * 
   * @param String $method post|get
   * @return Boolean 
   */
  private static function isEnabled($method)
  {
    $global = self::getConfig('enabled', false);
    $setting = self::getConfig('enabled.' . $method, false);
    return $global === true || $setting === true;
  }

  /**
   * Subfunction of findPageBySlug.
   * 
   * @param Mixed $collection 
   * @param Mixed $lang 
   * @param Mixed $keys 
   * @return Mixed 
   */
  private static function findPageBySlugRec($collection, $lang, $keys)
  {
    $key = array_shift($keys);
    foreach ($collection as $page) {
      if ($page->slug($lang) === $key) {
        if (count($keys) > 0) {
          return self::findPageBySlugRec($page->children(), $lang, $keys);
        } else {
          return $page;
        }
      }
    }
    return null;
  }

  /**
  * Normalize a value.
  *
  * @param Mixed $value 
  * @param Boolean $trim 
  * @param Boolean $strtolower 
  * @return Object|array|bool|float|string 
  */
  public static function typecast ($value, $trim = false, $strtolower = false)
  {
    if (is_object($value) || is_array($value) || is_bool($value)) {
      return $value;
    }
    $value = $trim ? trim($value) : $value;
    if (preg_match('/^[-+]?[0-9]*\.?[0-9]+$/', $value)) {
      return (float) $value;
    }
    $value = (string) $value;
    $value = $strtolower ? strtolower($value) : $value;
    return $value;
  }

  /**
   * Normalize a bool value.
   * 
   * @param Mixed $value 
   * @param Mixed $defaultReturn 
   * @return Mixed 
   */
  public static function typecastBool ($value, $defaultReturn = null)
  {
    if (
      $value === 1 ||
      $value === true ||
      strtolower(trim($value)) === 'true') {
        return true;
    } elseif (
      $value === 0 ||
      $value === false ||
      strtolower(trim($value)) === 'false') {
        return false;
    }
    return $defaultReturn;
  }

  /**
   * Normalize an array.
   *
   * @param Array $arr the array to normalise
   * @param Array|bool $norm_values normalise all values or the given
   * @return Array 
   */
  public static function normaliseArray ($arr, $norm_values = false)
  {
    $res = [];
    foreach ($arr as $key => $value) {
      $key = self::typecast($key, true, true);
      if (is_array($value)) {
        $res[$key] = self::normaliseArray(
          $value,
          (is_array($norm_values) && in_array($key, $norm_values)) ? true : $norm_values
        );
      } elseif ($norm_values === true || (is_array($norm_values) && in_array($key, $norm_values))) {
        $res[$key] = self::typecast($value, true, true);
      } else {
        $res[$key] = $value;
      }
    }
    return $res;
  }

  /**
   * Get host inforamtion about API and frontend.
   * 
   * @param String|Null $lang 
   * @return Array
   */
  public static function getHosts ($lang = null)
  {
    if (!$lang) {
      $lang = '__default__';
    }
    if (!isset(self::$hosts[$lang])) {
      self::$hosts[$lang] = [];
      self::$hosts[$lang]['self'] = [];
      self::$hosts[$lang]['referer'] = [];
      $backend = self::parseUrl(site()->url($lang));
      self::$hosts[$lang]['self']['host'] = $backend['host'];
      self::$hosts[$lang]['self']['port'] = isset($backend['port']) ? $backend['port'] : null;
      if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = self::parseUrl($_SERVER['HTTP_REFERER']);
        self::$hosts[$lang]['referer']['host'] = $referer['host'];
        self::$hosts[$lang]['referer']['port'] = isset($referer['port']) ? $referer['port'] : null;
      } else {
        self::$hosts[$lang]['referer']['host'] = self::$hosts[$lang]['self']['host'];
        self::$hosts[$lang]['referer']['port'] = self::$hosts[$lang]['self']['port'];
      }

      // don't care about proxys, too complicated for our purpose
      self::$hosts[$lang]['referer']['ip'] = getenv('HTTP_CLIENT_IP') ? getenv('HTTP_CLIENT_IP') : getenv('REMOTE_ADDR');
    }
    return self::$hosts[$lang];
  }
  
  /**
   * Parsing url in parts.
   * 
   * @param String $href 
   * @return Array|String|Integer|Boolean|Null 
   */
  public static function parseUrl($href)
  {
    $parts = parse_url($href);

    // doing some normalization
    if (isset($parts['scheme'])) {
      $parts['scheme'] = strtolower($parts['scheme']);
    }
    if (isset($parts['host'])) {
      $parts['host'] = strtolower($parts['host']);
    }
    if (isset($parts['port'])) {
      $parts['port'] = (int) $parts['port'];
    }
    if (isset($parts['path'])) {
      $parts['path'] = trim(strtolower($parts['path']), '/');
      if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['mailto', 'tel'])) {
        $parts['path'] = '/' . $parts['path'];
      }
    }
    if (isset($parts['fragment'])) {
      if (strpos($parts['fragment'], '?') === false) {
        $hash = $parts['fragment'];
        $query = null;
      } else {
        $hash = substr($parts['fragment'], 0, strpos($parts['fragment'], '?') - 1);
        $query = substr($parts['fragment'], strpos($parts['fragment'], '?'));
      }
      if (!empty($hash)) {
        $parts['hash'] = $hash;
      }
      if (!empty($query)) {
        $parts['query'] = $query;
      }
      unset($parts['fragment']);
    }
    return $parts;
  }

  /**
   * Build the url, reverse of parseUrl().
   * 
   * @param Array $parts 
   * @return String 
   */
  public static function buildUrl($parts)
  {
    return (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
      ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
      (isset($parts['user']) ? "{$parts['user']}" : '') .
      (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
      (isset($parts['user']) ? '@' : '') .
      (isset($parts['host']) ? "{$parts['host']}" : '') .
      (isset($parts['port']) ? ":{$parts['port']}" : '') .
      (isset($parts['path']) ? "{$parts['path']}" : '') .
      (isset($parts['hash']) ? "#{$parts['hash']}" : '') .
      (isset($parts['query']) ? "?{$parts['query']}" : '');
  }
}
