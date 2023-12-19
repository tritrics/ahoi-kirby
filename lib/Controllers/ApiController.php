<?php

namespace Tritrics\AflevereApi\v1\Controllers;

use Kirby\Cms\Response;
use Kirby\Exception\Exception;
use Tritrics\AflevereApi\v1\Services\EmailService;
use Tritrics\AflevereApi\v1\Factories\ModelFactory;
use Tritrics\AflevereApi\v1\Services\ApiService;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Services\RequestService;
use Tritrics\AflevereApi\v1\Services\InfoService;
use Tritrics\AflevereApi\v1\Services\NodeService;
use Tritrics\AflevereApi\v1\Services\NodesService;

class ApiController
{
  /**
   * Constructor, invokes the hooks of ModelFactory, in case other
   * plugins define own model-classes for special field-types.
   * @return void 
   */
  public function __construct ()
  {
    ModelFactory::hooks();
  }

  /**
   * Get a list of defined languages
   * @return Response|array 
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
   * @param string|null $lang
   * @param string|null $slug
   * @return Response|array|void 
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
   * @param string|null $lang
   * @param string|null $slug
   * @return Response|array|void 
   */
  public function node($lang, $slug)
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
      return NodeService::get($node, $lang, RequestService::getFields($request));
    } catch (Exception $e) {
      return ApiService::fatal($e->getMessage());
    }
  }

  /**
   * Get the children of a page, optionally filtered, limited etc.
   * @param string|null $lang
   * @param string|null $slug
   * @return Response|array 
   */
  public function nodes($lang, $slug)
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
      return NodesService::get($node, $lang, $params);
    } catch (Exception $e) {
      return ApiService::fatal($e->getMessage());
    }
  }

  /**
   * Handle all post-actions (only 'email' so far)
   * @param mixed $lang 
   * @param mixed $action 
   * @return Response
   */
  public function form($lang, $action)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ApiService::ok();
    }

    try {
      if ( ! ApiService::isEnabledForm()) {
        return ApiService::disabled();
      }
      $lang = trim(strtolower($lang));
      if ( ! kirby()->languages()->has($lang)) {
        return ApiService::invalidLang();
      }
      $data = $request->data();
      $action = trim(strToLower($action));
      switch ($action) {
        case 'email':
          $msg = EmailService::send($lang, $data);
          break;
        default:
          $msg = "Action is not given";
      }
      if ($msg) {
        return ApiService::notAllowed($msg);
      }
      return ApiService::ok();
    } catch (Exception $e) {
      return ApiService::fatal($e->getMessage());
    }
  }
}
