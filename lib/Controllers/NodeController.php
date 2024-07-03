<?php

namespace Tritrics\Ahoi\v1\Controllers;

use Kirby\Exception\Exception;
use Kirby\Http\Response as KirbyResponse;
use Tritrics\Ahoi\v1\Data\Response;
use Tritrics\Ahoi\v1\Factories\ModelFactory;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\RequestHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;
use Tritrics\Ahoi\v1\Services\NodeService;

/**
 * API Controller
 * Entry point for functions which return data from Kirby.
 * Basic checks and delegation to services.
 */
class NodeController
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
   * Get a file
   */
  public function file(?string $lang, ?string $slug): KirbyResponse
  {
    $Response = new Response('site', $lang, $slug);
    if (!ConfigHelper::isEnabledFile()) {
      return $Response->getDisabled();
    }
    return $this->get($Response, $lang, $slug);
  }

  /**
   * get response
   */
  public function get(Response $Response, ?string $lang, ?string $slug): KirbyResponse
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
        $node = KirbyHelper::findAny($lang, $slug);
        if (!$node) {
          return $Response->getNotFound();
        }
      }
      return $Response->get(
        NodeService::get($node, $lang, RequestHelper::getFields($request))
      );
    } catch (Exception $e) {
      return $Response->getFatal($e->getMessage());
    }
  }

  /**
   * Get a page
   */
  public function page(?string $lang, ?string $slug): KirbyResponse
  {
    $Response = new Response('page', $lang, $slug);
    if (!ConfigHelper::isEnabledPage()) {
      return $Response->getDisabled();
    }
    return $this->get($Response, $lang, $slug);
  }
}
