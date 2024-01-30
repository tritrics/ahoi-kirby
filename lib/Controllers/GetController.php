<?php

namespace Tritrics\AflevereApi\v1\Controllers;

use Kirby\Cms\Response;
use Kirby\Exception\Exception;
use Tritrics\AflevereApi\v1\Factories\ModelFactory;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Services\LanguageService;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;
use Tritrics\AflevereApi\v1\Helper\ResponseHelper;
use Tritrics\AflevereApi\v1\Helper\KirbyHelper;
use Tritrics\AflevereApi\v1\Services\InfoService;
use Tritrics\AflevereApi\v1\Services\PageService;
use Tritrics\AflevereApi\v1\Services\PagesService;

/**
 * API Controller
 * Entry point for functions which return data from Kirby.
 * Basic checks and delegation to services.
 */
class GetController
{
  /**
   * Constructor, invokes the hooks of ModelFactory, in case other
   * plugins define own model-classes for special field-types.
   */
  public function __construct ()
  {
    ModelFactory::hooks();
  }

  /**
   * Get general information, i.e. defined languages 
   */
  public function info (): Response
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ResponseHelper::ok();
    }
    try {
      if ( !ConfigHelper::isEnabledInfo()) {
        return ResponseHelper::disabled();
      }
      return ResponseHelper::json(InfoService::get());
    } catch (Exception $e) {
      return ResponseHelper::fatal($e->getMessage());
    }
  }

  /**
   * Get a single language
   */
  public function language(?string $lang): Response
  {
    $request = kirby()->request();
    try {
      if (!ConfigHelper::isEnabledLanguage()) {
        return ResponseHelper::disabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return ResponseHelper::invalidLang();
      }
      return ResponseHelper::json(LanguageService::get($lang));
    } catch (Exception $e) {
      return ResponseHelper::fatal($e->getMessage());
    }
  }

  /**
   * Get a single node
   */
  public function page(?string $lang, ?string $slug): Response
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ResponseHelper::ok();
    }

    try {
      if ( !ConfigHelper::isEnabledPage()) {
        return ResponseHelper::disabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return ResponseHelper::invalidLang();
      }
      if ($slug === null) {
        $node = site();
      } else {
        $node = KirbyHelper::findPageBySlug($lang, $slug);
        if (!$node || $node->isDraft()) {
          return ResponseHelper::notFound();
        }
      }
      return ResponseHelper::json(
        PageService::get($node, $lang, RequestHelper::getFields($request))
      );
    } catch (Exception $e) {
      return ResponseHelper::fatal($e->getMessage());
    }
  }

  /**
   * Get the children of a page, optionally filtered, limited etc.
   */
  public function pages(?string $lang, ?string $slug): Response
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return ResponseHelper::ok();
    }
    try {
      if ( !ConfigHelper::isEnabledPages()) {
        return ResponseHelper::disabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return ResponseHelper::invalidLang();
      }
      if ($slug === null) {
        $node = site();
      } else {
        $node = KirbyHelper::findPageBySlug($lang, $slug);
        if (!$node || $node->isDraft()) {
          return ResponseHelper::notFound();
        }
      }
      $params = [
        'page' => RequestHelper::getPage($request),
        'limit' => RequestHelper::getLimit($request),
        'order' => RequestHelper::getOrder($request),
        'fields' => RequestHelper::getFields($request),
        'filter' => RequestHelper::getFilter($request),
      ];
      return ResponseHelper::json(PagesService::get($node, $lang, $params));
    } catch (Exception $e) {
      return ResponseHelper::fatal($e->getMessage());
    }
  }
}
