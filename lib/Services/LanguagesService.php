<?php

namespace Tritrics\AflevereApi\v1\Services;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Services\ApiService;
use Tritrics\AflevereApi\v1\Models\LanguageModel;

class LanguagesService
{
  /** */
  private static $slugs = [];

  public static function get($lang)
  {
    $language = self::getLanguage($lang);
    $res = ApiService::initResponse();
    $body = $res->add('body', new LanguageModel($language, null, null, true));
    return $res->get();
  }

  /**
   * Languages for intern use
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
   * Multilang-site is defined in config.php: languages => true and the existance
   * of at least one language.
   * @return Language|null 
   */
  public static function isMultilang()
  {
    // not working correctly: true, even if config is false
    // return kirby()->multilang() ? true : false;
    return kirby()->option('languages', false);
  }

  /**
   * all languages
   */
  public static function getLanguages ()
  {
    return kirby()->languages();
  }

  /**
   * a single language
   */
  public static function getLanguage($code)
  {
    if (!self::isMultilang() || !self::isValid($code)) {
      return null;
    }
    return kirby()->language($code);
  }

  /**
   * the default language
   */
  public static function getDefault()
  {
    if (!self::isMultilang()) {
      return null;
    }
    return self::getLanguages()->default();
  }

  /**
   * Language count, 0 if multilang = false
   * @return int|int<0, max> 
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
   */
  public static function isValid ($lang)
  {
    if (!self::isMultilang()) {
      return true; // why??
    }
    return self::getLanguages()->has($lang);
  }

  /**
   * get lang prefix like "en" or "" if url set to ''
   * no leading slash!
   * @param string $code
   * @return string
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
   * get locale (de_DE)
   * @param mixed $code 
   * @return array|string 
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
   * Function will fail with fatal error, if not all nodes exists in langfile (Kirby-bug)
   * @param object $language
   * @return string|array
   */
  // private static function getLocale ($language)
  // {
  //   $locale = $language->locale(LC_ALL);
  //   if ($locale) {
  //     return $locale;
  //   }
  //   $locale = [];
  //   $locale['LC_COLLATE'] = $language->locale(LC_COLLATE);
  //   $locale['LC_CTYPE'] = $language->locale(LC_CTYPE);
  //   $locale['LC_MONETARY'] = $language->locale(LC_MONETARY);
  //   $locale['LC_NUMERIC'] = $language->locale(LC_NUMERIC);
  //   $locale['LC_TIME'] = $language->locale(LC_TIME);
  //   $locale['LC_MESSAGES'] = $language->locale(LC_MESSAGES);
  //   return $locale;
  // }

  public static function getUrl($code, $slug): string
  {
    return '/' . trim(self::getSlug($code) . '/' . $slug, '/');
  }
}
