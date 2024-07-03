<?php

namespace Tritrics\Ahoi\v1\Controllers;

use Kirby\Exception\Exception;
use Kirby\Http\Response as KirbyResponse;
use Tritrics\Ahoi\v1\Data\Response;
use Tritrics\Ahoi\v1\Factories\ModelFactory;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\RequestHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;
use Tritrics\Ahoi\v1\Services\CollectionService;

/**
 * API Controller
 * Entry point for functions which return data from Kirby.
 * Basic checks and delegation to services.
 */
class CollectionController
{
  /**
   * Constructor, invokes the hooks of ModelFactory, in case other
   * plugins define own model-classes for special field-types.
   */
  public function __construct()
  {
    ModelFactory::hooks();
  }

  /**
   * Get the files of a page, optionally filtered, limited etc.
   */
  public function files(?string $lang, ?string $slug): KirbyResponse
  {
    $Response = new Response('files', $lang, $slug);
    if (!ConfigHelper::isEnabledFiles()) {
      return $Response->getDisabled();
    }
    return $this->get($Response, $lang, $slug);
  }

  /**
   * get response
   */
  private function get(Response $Response, ?string $lang, ?string $slug): KirbyResponse
  {
    $request = kirby()->request();
    try {
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

  /**
   * Get the children of a page, optionally filtered, limited etc.
   */
  public function pages(?string $lang, ?string $slug): KirbyResponse
  {
    $Response = new Response('pages', $lang, $slug);
    if (!ConfigHelper::isEnabledPages()) {
      return $Response->getDisabled();
    }
    return $this->get($Response, $lang, $slug);
  }
}
