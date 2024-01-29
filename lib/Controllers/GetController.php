<?php

namespace Tritrics\AflevereApi\v1\Controllers;

use Kirby\Exception\Exception;
use Tritrics\AflevereApi\v1\Factories\ModelFactory;
use Tritrics\AflevereApi\v1\Helper\GlobalHelper;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;
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
   * @return Void 
   */
  public function __construct ()
  {
    ModelFactory::hooks();
  }

  /**
   * Get general information, i.e. defined languages
   * 
   * @return Array 
   */
  public function info ()
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return GlobalHelper::ok();
    }
    try {
      if ( ! GlobalHelper::isEnabledInfo()) {
        return GlobalHelper::disabled();
      }
      return InfoService::get();
    } catch (Exception $e) {
      return GlobalHelper::fatal($e->getMessage());
    }
  }

  /**
   * Get a single language
   * 
   * @param String|Null $lang
   * @param String|Null $slug
   * @return Array
   */
  public function language($lang)
  {
    $request = kirby()->request();
    try {
      if (!GlobalHelper::isEnabledLanguage()) {
        return GlobalHelper::disabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return GlobalHelper::invalidLang();
      }
      return LanguagesService::get($lang);
    } catch (Exception $e) {
      return GlobalHelper::fatal($e->getMessage());
    }
  }

  /**
   * Get a single node
   * 
   * @param String|Null $lang
   * @param String|Null $slug
   * @return Array
   */
  public function page($lang, $slug)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return GlobalHelper::ok();
    }

    try {
      if ( ! GlobalHelper::isEnabledPage()) {
        return GlobalHelper::disabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return GlobalHelper::invalidLang();
      }
      if ($slug === null) {
        $node = site();
      } else {
        $node = GlobalHelper::findPageBySlug($lang, $slug);
        if (!$node || $node->isDraft()) {
          return GlobalHelper::notFound();
        }
      }
      return PageService::get($node, $lang, RequestHelper::getFields($request));
    } catch (Exception $e) {
      return GlobalHelper::fatal($e->getMessage());
    }
  }

  /**
   * Get the children of a page, optionally filtered, limited etc.
   * 
   * @param String|Null $lang
   * @param String|Null $slug
   * @return Array
   */
  public function pages($lang, $slug)
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return GlobalHelper::ok();
    }
    try {
      if ( ! GlobalHelper::isEnabledPages()) {
        return GlobalHelper::disabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return GlobalHelper::invalidLang();
      }
      if ($slug === null) {
        $node = site();
      } else {
        $node = GlobalHelper::findPageBySlug($lang, $slug);
        if (!$node || $node->isDraft()) {
          return GlobalHelper::notFound();
        }
      }
      $params = [
        'page' => RequestHelper::getPage($request),
        'limit' => RequestHelper::getLimit($request),
        'order' => RequestHelper::getOrder($request),
        'fields' => RequestHelper::getFields($request),
        'filter' => RequestHelper::getFilter($request),
      ];
      return PagesService::get($node, $lang, $params);
    } catch (Exception $e) {
      return GlobalHelper::fatal($e->getMessage());
    }
  }
}
