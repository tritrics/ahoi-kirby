<?php

namespace Tritrics\Ahoi\v1\Services;

use Kirby\Exception\LogicException;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Models\LanguageModel;
use Tritrics\Ahoi\v1\Helper\LanguagesHelper;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;

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
    $languageDefault = LanguagesHelper::getDefault();
    $language = LanguagesHelper::get($lang);
    $body = new LanguageModel($language);
    $separator = ConfigHelper::getconfig('field_name_separator', '');

    $fields = new Collection();
    $translations = $language->translations();
    foreach ($languageDefault->translations() as $key => $foo) {
      $value = isset($translations[$key]) ? $translations[$key] : '';
      if ($separator) {
        $key = explode($separator, $key);
      }
      $fields->add($key, [
        'type' => 'string',
        'value' => $value
      ]);
    }
    $body->add('fields', $fields);
    return $body;
  }
}
