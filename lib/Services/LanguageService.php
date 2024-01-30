<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Exception\LogicException;
use Tritrics\AflevereApi\v1\Helper\ResponseHelper;
use Tritrics\AflevereApi\v1\Models\LanguageModel;
use Tritrics\AflevereApi\v1\Helper\KirbyHelper;

/**
 * Service for API's language interface and all language related functions.
 */
class LanguageService
{
  /**
   * Main method to respond to "language" action.
   * 
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get(?string $lang): array
  {
    $language = KirbyHelper::getLanguage($lang);
    $res = ResponseHelper::getHeader();
    if ($language !== null) {
      $res->add('body', new LanguageModel($language, null, null, true));
    }
    return $res->get();
  }
}
