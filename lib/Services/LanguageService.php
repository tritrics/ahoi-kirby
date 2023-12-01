<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Cms\Language;
use Kirby\Exception\LogicException;
use Tritrics\AflevereApi\v1\Data\Collection;

class LanguageService
{
  /** */
  private static $slugs = [];

  /**
   * Languages for use in API response
   */
  public static function get ()
  {
    $languages = kirby()->languages();
    $home = kirby()->site()->homePage();
    $res = new Collection();
    foreach ($languages as $language) {
      $code = trim(strtolower($language->code()));
      $lang = new Collection();
      $lang->add('type', 'language');
      $meta = $lang->add('meta');
      $meta->add('code', $code);
      $meta->add('default', $language->isDefault());
      $meta->add('locale', self::getLocale($code));
      $meta->add('direction', $language->direction());
      $lang->add('link', LinkService::getPage(
        self::getUrl(self::getSlug($code), $home->uri($code))
      ));
      $terms = $language->translations();
      if (count($terms) > 1) {
        $lang->add('terms', $terms);
      }
      $lang->add('value', $language->name());
      $res->add($code, $lang);
    }
    return $res;
  }

  /**
   * Languages for intern use
   */
  public static function list ()
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
    }
    return $res;
  }

  /**
   * Language count, 0 if multilang = false
   * @return int|int<0, max> 
   * @throws LogicException 
   */
  public static function count()
  {
    if (!kirby()->option('languages', false)) {
      return 0;
    }
    $languages = kirby()->languages();
    return count($languages);
  }

  /**
   * Multilang-site is defined in config.php: languages => true and the existance
   * of at least one language.
   * @return Language|null 
   */
  public static function isMultilang ()
  {
    // not working correctly: true, even if config is false
    // return kirby()->multilang() ? true : false;
    return self::count() > 0;
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

  private static function getUrl($langSlug, $slug): string
  {
    return '/' . trim($langSlug . '/' . $slug, '/');
  }
}
