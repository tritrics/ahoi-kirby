<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Cms\Language;
use Kirby\Cms\Languages;
use Kirby\Exception\LogicException;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Services\ApiService;
use Tritrics\AflevereApi\v1\Models\LanguageModel;

/**
 * Service for API's language interface and all language related functions.
 */
class LanguagesService
{
  /**
   * Intern cache for language slugs.
   * 
   * @var Array
   */
  private static $slugs = [];

  /**
   * Main method to respond to "language" action.
   * 
   * @return Response 
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get($lang)
  {
    $language = self::getLanguage($lang);
    $res = ApiService::initResponse();
    $body = $res->add('body', new LanguageModel($language, null, null, true));
    return $res->get();
  }

  /**
   * List availabe languages for intern use.
   * 
   * @return Collection 
   */
  public static function list ()
  {
    $home = kirby()->site()->homePage();
    $res = new Collection();
    foreach(self::getLanguages() as $language) {
      $res->add($language->code(), [
        'name' => $language->name(),
        'slug' => self::getSlug($language->code()),
        'default' => $language->isDefault(),
        'locale' => self::getLocale($language->code()),
        'direction' => $language->direction(),
        'homeslug' => $home->uri($language->code())
      ]);
    }
    return $res;
  }

  /**
   * Check if installation is multilang.
   * Multilang-site is defined in config.php: languages => true.
   * 
   * @return Boolean 
   */
  public static function isMultilang()
  {
    // kirby()->multilang() is not working correctly.
    // It's returning true, even if config is false.
    return kirby()->option('languages', false);
  }

  /**
   * Get all languages as Kirby object.
   * 
   * @return Languages 
   * @throws LogicException 
   */
  public static function getLanguages ()
  {
    return kirby()->languages();
  }

  /**
   * Get a single language as Kirby object defined by $code.
   * 
   * @param String $code 
   * @return Language|null 
   * @throws LogicException 
   */
  public static function getLanguage($code)
  {
    if (!self::isMultilang() || !self::isValid($code)) {
      return null;
    }
    return kirby()->language($code);
  }

  /**
   * Get the default language as Kirby object.
   * 
   * @return Language|null 
   * @throws LogicException 
   */
  public static function getDefault()
  {
    if (!self::isMultilang()) {
      return null;
    }
    return self::getLanguages()->default();
  }

  /**
   * Get the language count if it's a multilang installation.
   * 
   * @return int
   * @throws LogicException 
   */
  public static function count()
  {
    if (!self::isMultilang()) {
      return 0;
    }
    return self::getLanguages()->count();
  }

  /**
   * Check if a given language code is valid.
   * 
   * @param Mixed $code 
   * @return Boolean 
   * @throws LogicException 
   */
  public static function isValid ($code)
  {
    if (!self::isMultilang()) {
      return true;
    }
    return self::getLanguages()->has($code);
  }

  /**
   * Get the slug for a given language.
   * 
   * @param String $code 
   * @return String 
   * @throws LogicException 
   */
  public static function getSlug ($code) {
    if (!self::isMultilang()) {
      return '';
    }
    if (!isset(self::$slugs[$code])) {
      $language = self::getLanguage($code);
      $url = parse_url($language->url());

      // setting url = '/' means there is no prefix for default langauge
      // Frontend has it's own logic and always uses code for api-requests.
      // The slug-property is only the vue-router to show or not the code in routes.
      if($language->isDefault() && (!isset($url['path']) || $url['path'] === '')) {
        self::$slugs[$code] = '';
      } else {
        self::$slugs[$code] = $language->code();
      }
    }
    return self::$slugs[$code];
  }

  /**
   * Get the locale for a given language.
   * 
   * @param String $code 
   * @return Array|string 
   * @throws LogicException 
   */
  public static function getLocale ($code)
  {
    if (!self::isMultilang()) {
      return '';
    }
    $language = self::getLanguage($code);
    $php_locale = $language->locale(LC_ALL);
    return str_replace('_', '-', $php_locale);
  }

  /**
   * Get the url for a given language.
   * 
   * @param String $code 
   * @param String $slug 
   * @return String 
   * @throws LogicException 
   */
  public static function getUrl($code, $slug): string
  {
    return '/' . trim(self::getSlug($code) . '/' . $slug, '/');
  }
}
