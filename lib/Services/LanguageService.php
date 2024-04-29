<?php

namespace Tritrics\Tric\v1\Services;

use Kirby\Exception\LogicException;
use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Models\LanguageModel;
use Tritrics\Tric\v1\Helper\KirbyHelper;

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
    $language = KirbyHelper::getLanguage($lang);
    $body = new LanguageModel($language);

    $terms = new Collection();
    foreach ($language->translations() as $key => $value) {
      $terms->add($key, [
        'type' => 'string',
        'value' => $value
      ]);
    }
    $body->add('fields', $terms);
    return $body;
  }
}
