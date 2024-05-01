<?php

namespace Tritrics\Tric\v1\Controllers;

use Kirby\Exception\Exception;
use Kirby\Http\Response as KirbyResponse;
use Tritrics\Tric\v1\Data\Response;
use Tritrics\Tric\v1\Factories\ModelFactory;
use Tritrics\Tric\v1\Helper\ConfigHelper;
use Tritrics\Tric\v1\Services\LanguageService;
use Tritrics\Tric\v1\Helper\RequestHelper;
use Tritrics\Tric\v1\Helper\KirbyHelper;
use Tritrics\Tric\v1\Services\InfoService;
use Tritrics\Tric\v1\Services\FieldsService;
use Tritrics\Tric\v1\Services\CollectionService;

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
  public function info (): KirbyResponse
  {
    $Response = new Response('info');
    try {
      if ( !ConfigHelper::isEnabledInfo()) {
        return $Response->getDisabled();
      }
      return $Response->get(InfoService::get());
    } catch (Exception $e) {
      return $Response->getFatal($e->getMessage());
    }
  }

  /**
   * Get a single language
   */
  public function language(?string $lang): KirbyResponse
  {
    $Response = new Response('language', $lang);
    try {
      if (!ConfigHelper::isEnabledLanguage()) {
        return $Response->getDisabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return $Response->getInvalidLang();
      }
      return $Response->get(LanguageService::get($lang));
    } catch (Exception $e) {
      return $Response->getFatal($e->getMessage());
    }
  }

  /**
   * Get a single node
   */
  public function fields(?string $lang, ?string $slug): KirbyResponse
  {
    $Response = new Response('fields', $lang, $slug);
    $request = kirby()->request();
    try {
      if ( !ConfigHelper::isEnabledFields()) {
        return $Response->getDisabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return $Response->getInvalidLang();
      }
      if ($slug === null) {
        $node = site();
      } else {
        $node = KirbyHelper::findAny($lang, $slug);
        if (!$node) {
          return $Response->getNotFound();
        }
      }
      return $Response->get(
        FieldsService::get($node, $lang, RequestHelper::getFields($request))
      );
    } catch (Exception $e) {
      return $Response->getFatal($e->getMessage());
    }
  }

  /**
   * Get the children of a page, optionally filtered, limited etc.
   */
  public function pages(?string $lang, ?string $slug): KirbyResponse
  {
    $Response = new Response('pages', $lang, $slug);
    return $this->collection($Response, $lang, $slug);
  }

  /**
   * Get the children of a page, optionally filtered, limited etc.
   */
  public function files(?string $lang, ?string $slug): KirbyResponse
  {
    $Response = new Response('files', $lang, $slug);
    return $this->collection($Response, $lang, $slug);
  }

  /**
   * pages and files are similar
   */
  private function collection(Response $Response, ?string $lang, ?string $slug): KirbyResponse
  {
    $request = kirby()->request();
    if ($request->method() === 'OPTIONS') {
      return $Response->get();
    }
    try {
      if (!in_array($Response->request, ['pages', 'files'])) {
        return $Response->getFatal();
      }
      if (
        ($Response->request === 'pages' && !ConfigHelper::isEnabledPages()) ||
        ($Response->request === 'files' && !ConfigHelper::isEnabledFiles())
      ) {
        return $Response->getDisabled();
      }
      $lang = RequestHelper::getLang($lang);
      if ($lang === null) {
        return $Response->getInvalidLang();
      }
      if ($slug === null) {
        $node = site();
      } else {
        $node = KirbyHelper::findPage($lang, $slug);
        if (!$node || $node->isDraft()) {
          return $Response->getNotFound();
        }
      }
      $params = [
        'set' => RequestHelper::getSet($request),
        'limit' => RequestHelper::getLimit($request),
        'order' => RequestHelper::getOrder($request),
        'fields' => RequestHelper::getFields($request),
        'filter' => RequestHelper::getFilter($request),
      ];
      return $Response->get(CollectionService::get($Response->request, $node, $lang, $params));
    } catch (Exception $e) {
      return $Response->getFatal($e->getMessage());
    }
  }
}
