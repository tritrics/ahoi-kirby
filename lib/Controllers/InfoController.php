<?php

namespace Tritrics\Ahoi\v1\Controllers;

use Kirby\Exception\Exception;
use Kirby\Http\Response as KirbyResponse;
use Tritrics\Ahoi\v1\Data\Response;
use Tritrics\Ahoi\v1\Factories\ModelFactory;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Services\InfoService;

/**
 * API Controller
 * Entry point for functions which return data from Kirby.
 * Basic checks and delegation to services.
 */
class InfoController
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
   * Get general information, i.e. defined languages 
   */
  public function info(): KirbyResponse
  {
    $Response = new Response('info');
    try {
      if (!ConfigHelper::isEnabledInfo()) {
        return $Response->getDisabled();
      }
      return $Response->get(InfoService::get());
    } catch (Exception $e) {
      return $Response->getFatal($e->getMessage());
    }
  }
}
