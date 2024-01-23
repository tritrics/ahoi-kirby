<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Cms\Response;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Services\LanguagesService;

/**
 * Service for underlying API functions, checks and initializing responses.
 */
class ApiService
{
  /**
   * The API version
   * 
   * @var String
   */
  public static $version = 'v1';

  /**
   * The plugin, under which the plugin is registered in Kirby
   * 
   * @var String
   */
  public static $pluginName = 'tritrics/aflevere-api-v1';

  /**
   * The namespace for dynamic imports in php
   * 
   * @var String
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
   * @param String $node 
   * @param Mixed $default 
   * @return Mixed 
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
   * @param Mixed $slug 
   * @return Boolean 
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
}
