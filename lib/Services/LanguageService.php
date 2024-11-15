<?php

namespace Tritrics\Ahoi\v1\Services;

use Kirby\Exception\LogicException;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Models\LanguageModel;

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
  public static function get(?string $lang): Collection
  {
    return new LanguageModel(null, null, $lang, [ '*' ], true);
  }
}
