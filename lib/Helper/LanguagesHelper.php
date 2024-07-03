<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Cms\Language;
use Kirby\Cms\Languages;
use Kirby\Exception\LogicException;
use Tritrics\Ahoi\v1\Data\Collection;

/**
 * Language related helper functions.
 */
class LanguagesHelper
{
  /**
   * Cache some data.
   */
  private static $cache = [];

  /**
   * Get the language count if it's a multilang installation.
   */
  public static function count(): int
  {
    if (!ConfigHelper::isMultilang()) {
      return 0;
    }
    return self::getAll()->count();
  }

  /**
   * Get a single language as Kirby object defined by $code.
   */
  public static function get(?string $code): ?Language
  {
    try {
      return kirby()->language($code);
    } catch (LogicException $E) {
      return null;
    }
  }

  /**
   * Get all languages as Kirby object.
   */
  public static function getAll(): ?Languages
  {
    try {
      return kirby()->languages();
    } catch (LogicException $E) {
      return null;
    }
  }

  /**
   * List availabe languages for intern use.
   */
  public static function getCodes(): array
  {
    $res = [];
    foreach (self::getAll() as $language) {
      $res[] = $language->code();
    }
    return $res;
  }

  /**
   * Get the default language as Kirby object.
   */
  public static function getDefault(): ?Language
  {
    if (!ConfigHelper::isMultilang()) {
      return null;
    }
    return self::getAll()->default();
  }

  /**
   * Get link (optional) prefix, defined in languages/[lang].php > url.
   * Link prefix is the path-part of the setting (@see getOrigin()).
   * Default link prefix is the language-code.
   */
  public static function getLangSlug(string $code): string|null
  {
    if (self::isValid($code)) {
      $language = self::get($code);
      $url = UrlHelper::parse($language->url());
      $path = UrlHelper::buildPath($url);
      return $path === '/' ? '' : $path;
    }
    return null;
  }

  /**
   * Get the locale for a given language.
   */
  public static function getLocale(?string $code): string
  {
    if (!self::isValid($code)) {
      return '';
    }
    $language = self::get($code);
    $php_locale = $language->locale(LC_ALL);
    return str_replace('_', '-', $php_locale);
  }

  /**
   * Get the (optional) domain, defined in languages/[lang].php > url.
   * Origin is the domain-part of the setting (@see getLinkPrefix()).
   */
  public static function getOrigin(string $code): string
  {
    if (self::isValid($code)) {
      $language = self::get($code);
      $url = UrlHelper::parse($language->url());
      $urlHost = UrlHelper::buildHost($url);
      $self = UrlHelper::getSelfUrl();
      if ($self !== $urlHost) {
        return rtrim(UrlHelper::buildHost($url), '/');
      }
    }
    return '';
  }

  /**
   * Check if a given language code is valid.
   * empty string or null in non-multilang installation -> true
   * valid language code in multilang installation -> true
   * rest -> false
   */
  public static function isValid(?string $code): bool
  {
    if (ConfigHelper::isMultilang()) {
      return self::getAll()->has($code);
    }
    return empty($code) || $code === null;
  }
}
