<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Cms\Response;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Services\LanguagesService;

/**
 * Service for underlying API functions, checks and initializing responses.
 *
 * @package   AflevereAPI Services
 * @author    Michael Adams <ma@tritrics.dk>
 * @link      https://aflevereapi.dev
 * @copyright Michael Adams
 * @license   https://opensource.org/license/isc-license-txt/
 */
class ApiService
{
  /**
   * The API version
   * 
   * @var string
   */
  public static $version = 'v1';

  /**
   * The plugin, under which the plugin is registered in Kirby
   * 
   * @var string
   */
  public static $pluginName = 'tritrics/aflevere-api-v1';

  /**
   * The namespace for dynamic imports in php
   * 
   * @var string
   */
  public static $namespace = 'Tritrics\AflevereApi\v1';

  public static function getPluginVersion()
  {
    return kirby()->plugin(self::$pluginName)->version();
  }

  /**
   * Get setting from plugins config.php
   * example: tritrics.aflevere-api.v1.slug
   * 
   * @param string $node 
   * @param mixed $default 
   * @return mixed 
   */
  public static function getConfig($node, $default = false)
  {
    return kirby()->option(str_replace('/', '.', self::$pluginName) . '.' . $node, $default);
  }

  /**
   * Compute the base slug like /public-api/v1
   * 
   * @return null|string 
   */
  public static function getApiSlug()
  {
    $slug = trim(trim(self::getconfig('slug', ''), '/'));
    if (is_string($slug) && strlen($slug) > 0) {
      $slug = '/' . $slug;
    }
    return $slug . '/' . self::$version;
  }

  /**
   * Check, if a slug the backend-user enters, has a conflict with the API-Route
   * 
   * @param mixed $slug 
   * @return bool 
   */
  public static function isProtectedSlug($slug)
  {
    $path = strtolower(self::getconfig('slug'));
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
   * @param mixed $path 
   * @param bool $multilang
   * @return array 
   */
  public static function parsePath($path, $multilang)
  {
    $parts = array_filter(explode('/', $path));
    $lang = $multilang ? array_shift($parts) : null;
    $slug = count($parts) > 0 ? implode('/', $parts) : null;
    return [$lang, $slug];
  }

  /**
   * Check if "info" action is enabled.
   * 
   * @return bool 
   */
  public static function isEnabledInfo()
  {
    return self::isEnabled('info');
  }

  /**
   * Check if "language" action is enabled.
   * 
   * @return bool 
   */
  public static function isEnabledLanguage()
  {
    return self::isEnabled('language');
  }

  /**
   * Check if "page" action is enabled.
   * 
   * @return bool 
   */
  public static function isEnabledPage()
  {
    return self::isEnabled('page');
  }

  /**
   * Check if "pages" action is enabled.
   * 
   * @return bool 
   */
  public static function isEnabledPages()
  {
    return self::isEnabled('pages');
  }

  /**
   * Check if "form" action is enabled.
   * 
   * @return bool 
   */
  public static function isEnabledForm()
  {
    return self::isEnabled('form');
  }

  /**
   * Helper: Find a page by translated slug
   * (Kirby can only find by default slug)
   * 
   * @param mixed $lang 
   * @param mixed $slug 
   * @return mixed 
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
   * @param int $status 
   * @param string $msg 
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
   * @param string $msg 
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
   * @param string $msg 
   * @return Response 
   */
  public static function badRequest($msg = 'Bad Request')
  {
    return Response::json(self::initResponse(400, $msg)->get(), 400);
  }

  /**
   * Response: API is diabled.
   * 
   * @param string $msg 
   * @return Response 
   */
  public static function disabled($msg = 'API is disabled for this action')
  {
    return Response::json(self::initResponse(403, $msg)->get(), 403);
  }

  /**
   * Response: Not found.
   * 
   * @param string $msg 
   * @return Response 
   */
  public static function notFound($msg = 'Page is not found')
  {
    return Response::json(self::initResponse(404, $msg)->get(), 404);
  }

  /**
   * Response: Not Allowed.
   * 
   * @param string $msg 
   * @return Response 
   */
  public static function notAllowed($msg = 'Action not allowed')
  {
    return Response::json(self::initResponse(405, $msg)->get(), 405);
  }

  /**
   * Response: Internal Server Error.
   * 
   * @param string $msg 
   * @return Response 
   */
  public static function fatal($msg = 'Internal Server Error')
  {
    return Response::json(self::initResponse(500, $msg)->get(), 500);
  }

  /**
   * Check, if API's functions are enabled.
   * 
   * @param string $method post|get
   * @return bool 
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
   * @param mixed $collection 
   * @param mixed $lang 
   * @param mixed $keys 
   * @return mixed 
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
}
