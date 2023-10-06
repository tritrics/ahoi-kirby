<?php

namespace Tritrics\Api\Controllers;

use Kirby\Cms\Response;
use Kirby\Exception\Exception;
use Tritrics\Api\Services\EmailService;
use Tritrics\Api\Factories\ModelFactory;
use Tritrics\Api\Services\ApiService;
use Tritrics\Api\Services\LanguageService;
use Tritrics\Api\Services\RequestService;
use Tritrics\Api\Services\NodeService;
use Tritrics\Api\Services\CollectionService;

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
  public function languages ()
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ApiService::ok();
    }

    try {
      RequestService::getSleep($request); // Debugging
      if ( ! ApiService::isEnabledLanguages()) {
        return ApiService::disabled();
      }
      return LanguageService::languages();
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
      if ( ! ApiService::isEnabledNode()) {
        return ApiService::disabled();
      }
      if (!LanguageService::isValid($lang)) {
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
  public function collection ($lang, $slug)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ApiService::ok();
    }

    try {
      RequestService::getSleep($request); // Debugging
      if ( ! ApiService::isEnabledChildren()) {
        return ApiService::disabled();
      }
      if (!LanguageService::isValid($lang)) {
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
      return CollectionService::get($node, $lang, $params);
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
  public function submit ($lang, $action)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ApiService::ok();
    }

    try {
      if ( ! ApiService::isEnabledSubmit()) {
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
