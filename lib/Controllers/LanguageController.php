<?php

namespace Tritrics\Ahoi\v1\Controllers;

use Kirby\Exception\Exception;
use Kirby\Http\Response as KirbyResponse;
use Tritrics\Ahoi\v1\Data\Response;
use Tritrics\Ahoi\v1\Factories\ModelFactory;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Services\LanguageService;
use Tritrics\Ahoi\v1\Helper\RequestHelper;

/**
 * API Controller
 * Entry point for functions which return data from Kirby.
 * Basic checks and delegation to services.
 */
class LanguageController
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
}
