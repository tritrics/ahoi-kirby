<?php

namespace Tritrics\AflevereApi\v1\Controllers;

use Kirby\Cms\Response;
use Kirby\Exception\Exception;
use Tritrics\AflevereApi\v1\Services\ActionService;
use Tritrics\AflevereApi\v1\Factories\ModelFactory;
use Tritrics\AflevereApi\v1\Services\ApiService;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Services\RequestService;
use Tritrics\AflevereApi\v1\Services\InfoService;
use Tritrics\AflevereApi\v1\Services\PageService;
use Tritrics\AflevereApi\v1\Services\PagesService;

/**
 * API Controller
 * Entry point for API functions. Basic checks and delegation to services.
 */
class ApiController
{
  /**
   * Constructor, invokes the hooks of ModelFactory, in case other
   * plugins define own model-classes for special field-types.
   * @return Void 
   */
  public function __construct ()
  {
    ModelFactory::hooks();
  }

  /**
   * Get general information, i.e. defined languages
   * 
   * @return Response|Array 
   */
  public function info ()
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ApiService::ok();
    }

    try {
      if ( ! ApiService::isEnabledInfo()) {
        return ApiService::disabled();
      }
      return InfoService::get();
    } catch (Exception $e) {
      return ApiService::fatal($e->getMessage());
    }
  }

  /**
   * Get a single language
   * 
   * @param String|Null $lang
   * @param String|Null $slug
   * @return Response|Array|Void 
   */
  public function language($lang)
  {
    $request = kirby()->request();
    try {
      if (!ApiService::isEnabledLanguage()) {
        return ApiService::disabled();
      }
      if (!LanguagesService::isValid($lang)) {
        return ApiService::invalidLang();
      }
      return LanguagesService::get($lang);
    } catch (Exception $e) {
      return ApiService::fatal($e->getMessage());
    }
  }

  /**
   * Get a single node
   * 
   * @param String|Null $lang
   * @param String|Null $slug
   * @return Response|Array|Void 
   */
  public function page($lang, $slug)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ApiService::ok();
    }

    try {
      RequestService::getSleep($request); // Debugging
      if ( ! ApiService::isEnabledPage()) {
        return ApiService::disabled();
      }
      if (!LanguagesService::isValid($lang)) {
        return ApiService::invalidLang();
      }
      if ($slug === null) {
        $node = site();
      } else {
        $node = ApiService::findPageBySlug($lang, $slug);
        if (!$node || $node->isDraft()) {
          return ApiService::notFound();
        }
      }
      return PageService::get($node, $lang, RequestService::getFields($request));
    } catch (Exception $e) {
      return ApiService::fatal($e->getMessage());
    }
  }

  /**
   * Get the children of a page, optionally filtered, limited etc.
   * 
   * @param String|Null $lang
   * @param String|Null $slug
   * @return Response|Array 
   */
  public function pages($lang, $slug)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ApiService::ok();
    }

    try {
      RequestService::getSleep($request); // Debugging
      if ( ! ApiService::isEnabledPages()) {
        return ApiService::disabled();
      }
      if (!LanguagesService::isValid($lang)) {
        return ApiService::badRequest();
      }
      if ($slug === null) {
        $node = site();
      } else {
        $node = ApiService::findPageBySlug($lang, $slug);
        if (!$node || $node->isDraft()) {
          return ApiService::notFound();
        }
      }
      $params = [
        'page' => RequestService::getPage($request),
        'limit' => RequestService::getLimit($request),
        'order' => RequestService::getOrder($request),
        'fields' => RequestService::getFields($request),
        'filter' => RequestService::getFilter($request),
      ];
      return PagesService::get($node, $lang, $params);
    } catch (Exception $e) {
      return ApiService::fatal($e->getMessage());
    }
  }

  /**
   * Handle all post-actions
   * 
   * @param Mixed $lang 
   * @param Mixed $action 
   * @return Response
   */
  public function action($lang, $action)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ApiService::ok();
    }

    try {
      if ( ! ApiService::isEnabledAction()) {
        return ApiService::disabled();
      }
      $lang = trim(strtolower($lang));
      if ( ! kirby()->languages()->has($lang)) {
        return ApiService::invalidLang();
      }
      $data = $request->data();
      $action = trim(strToLower($action));
      return ActionService::do($lang, $action, $data);
    } catch (Exception $e) {
      return ApiService::fatal($e->getMessage());
    }
  }
}
