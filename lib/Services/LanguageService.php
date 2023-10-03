<?php

namespace Tritrics\Api\Services;

use Kirby\Cms\Language;
use Tritrics\Api\Data\Collection;

class LanguageService
{
  /** */
  private static $slugs = [];

  /**
   * API method
   */
  public static function languages ()
  {
    $res = ApiService::initResponse();
    $content = self::getAll();
    $res->add('content', $content);
    return $res->get();
  }

  /**
   * simple array with langs
   */
  public static function getAll ()
  {
    $home = kirby()->site()->homePage();
    $res = new Collection();
    foreach(kirby()->languages() as $language) {
      $lang = $res->add($language->code(), [
        'name' => $language->name(),
        'slug' => self::getSlug($language->code()),
        'default' => $language->isDefault(),
        'locale' => self::getLocale($language->code()),
        'direction' => $language->direction(),
        'homeslug' => $home->uri($language->code())
      ]);
      $translations = $language->translations();
      if (count($translations) > 1) {
        $lang->add('translations', $translations);
      }
    }
    return $res;
  }

  /**
   * Multilang-site is defined in config.php: languages => true
   * AND the existence of the folder site/languages (delete folder to have single-language setup)
   * @return Language|null 
   */
  public static function isMultilang ()
  {
    return kirby()->option('languages', false);

    // not working correctly: true, even if config is false
    // return kirby()->multilang() ? true : false;
  }

  /**
   * checks, if given language is valid
   * return given language, default language or null on non-lang-sites
   * @param string $lang
   * @return string|null
   */
  public static function isValid ($lang)
  {
    if ($lang === null && !self::isMultilang()) {
      return true;
    }
    return kirby()->languages()->has($lang);
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
      $language = kirby()->language($code);
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
    $language = kirby()->language($code);
    return $language->locale(LC_ALL);
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
}
