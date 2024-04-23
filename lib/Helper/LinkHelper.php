<?php

namespace Tritrics\AflevereApi\v1\Helper;

/**
 * Service for any kind of links (texts, page, file, user) to produce consistant output.
 */
class LinkHelper
{
  /**
   * 2-digit language code
   * 
   * @var string
   */
  private static $lang;


  /**
   * Detected slugs with starting slash.
   * 
   * @var array
   */
  private static $slugs = [
    'home' => null, // /home
    'media' => '', // /media, (string because strlen())
    'lang' => null // /en, if multilang-site
  ];

  /**
   * Build the path, ignoring all host-parts.
   */
  private static function buildPath(array $parts): string
  {
    return '' .
    (isset($parts['path']) ? "{$parts['path']}" : '') .
      (isset($parts['hash']) ? "#{$parts['hash']}" : '') .
      (isset($parts['query']) ? "?{$parts['query']}" : '');
  }

  /**
   * Build the url, reverse of parseUrl().
   */
  public static function buildUrl(array $parts): string
  {
    return '' .
    (isset($parts['scheme']) ? "{$parts['scheme']}:" : '') .
      ((isset($parts['user']) || isset($parts['host'])) ? '//' : '') .
      (isset($parts['user']) ? "{$parts['user']}" : '') .
      (isset($parts['pass']) ? ":{$parts['pass']}" : '') .
      (isset($parts['user']) ? '@' : '') .
      (isset($parts['host']) ? "{$parts['host']}" : '') .
      (isset($parts['port']) ? ":{$parts['port']}" : '') .
      (isset($parts['path']) ? "{$parts['path']}" : '') .
      (isset($parts['hash']) ? "#{$parts['hash']}" : '') .
      (isset($parts['query']) ? "?{$parts['query']}" : '');
  }

  /**
   * Get anchor.
   */
  public static function getAnchor(string $anchor, bool $title = null): array
  {
    if (!str_starts_with($anchor, '#')) {
      $anchor = '#' . $anchor;
    }
    $res = [
      'type' => 'anchor',
      'href' => $anchor
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    return $res;
  }

  /**
   * Get custom link.
   */
  public static function getCustom(string $link, ?string $title = null): array
  {
    $res = [
      'type' => 'custom',
      'href' => $link
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    return $res;
  }

  /**
   * Detects the linktype from a given $href.
   */
  public static function getInline(
    ?string $lang,
    string $href,
    ?string $title = null,
    bool $target = false
  ): array {

    // Initialization
    // do only once
    if (self::$lang !== $lang) {
      self::$lang = $lang;
      $home = self::parseUrl(site()->homePage()->uri(self::$lang));
      self::$slugs['home'] = $home['path'];
      $media = self::parseUrl(kirby()->url('media'));
      self::$slugs['media'] = $media['path'];
      self::$slugs['lang'] = '/' . LanguagesHelper::getSlug(self::$lang);
    }
    $hosts = RequestHelper::getHosts(self::$lang);

    // rewrite intern page and file links, which start with
    // /@/page and /@/file
    if (str_starts_with($href, '/@/page/')) {
      $uuid = str_replace('/@/page/', 'page://', $href);
      $page = kirby()->page($uuid);
      if ($page->exists()) {
        $href = $page->url();
      } else {
        return [];
      }
    } else if (str_starts_with($href, '/@/file/')) {
      $uuid = str_replace('/@/file/', 'file://', $href);
      $file = kirby()->file($uuid);
      if ($file->exists()) {
        $href = $file->url();
      } else {
        return [];
      }
    }

    // working with splitted url
    $parts = self::parseUrl($href);

    // email and tel
    if (isset($parts['scheme']) && isset($parts['path'])) {
      if ($parts['scheme'] === 'mailto') {
        return self::getEmail($parts['path'], $title);
      } else if ($parts['scheme'] === 'tel') {
        return self::getTel($parts['path'], $title);
      }
    }

    // anchor
    if (isset($parts['hash']) && !isset($parts['scheme']) && !isset($parts['host']) && !isset($parts['path'])) {
      return self::getAnchor($parts['hash'], $title);
    }

    // file links
    if (self::isInternLink($parts, $hosts['self'])) {
      if (isset($parts['path']) && substr($parts['path'], 0, strlen(self::$slugs['media'])) === self::$slugs['media']) {
        $parts['host'] =  $hosts['self']['host']; // make absolute links to be sure
        if ($hosts['self']['port']) {
          $parts['port'] = $hosts['self']['port'];
        } else {
          unset($parts['port']);
        }
        return self::getFile(self::buildUrl($parts), $title, $target);
      } 
    }

    // intern links
    // use buildPath() -> make intern
    if (self::isInternLink($parts, $hosts['self']) || self::isInternLink($parts, $hosts['referer'])) {
      return self::getPage(self::buildPath($parts), $title, $target);
    }

    // all other links
    return self::getUrl(self::buildUrl($parts), $title, $target);
  }

  /**
   * Get email link.
   */
  public static function getEmail(string $email, ?string $title = null): array
  {
    $res = [
      'type' => 'email',
      'href' => str_starts_with($email, 'mailto:') ? $email : 'mailto:' . $email
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    return $res;
  }

  /**
   * Get file (download) link.
   */
  public static function getFile(string $path, ?string $title = null, bool $blank = false): array
  {
    $res = [
      'type' => 'file',
      'href' => $path
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    if ($blank) {
      $res['target'] = '_blank';
    }
    return $res;
  }

  /**
   * Get page (intern) link.
   */
  public static function getPage(string $path, ?string $title = null, bool $blank = false): array
  {
    // check and correct links to home page(s)
    $parts = self::parseUrl($path);

    // path is empty, set path to homepage, optional with prepending lang
    if (!isset($parts['path']) || empty($parts['path']) || $parts['path'] === '/') {
      if (ConfigHelper::isMultilang()) {
        $parts['path'] = self::$slugs['lang'] . self::$slugs['home'];
      } else {
        $parts['path'] = self::$slugs['home'];
      }
    }

    // path is not empty in a multilang installation
    else if (ConfigHelper::isMultilang()) {
      $slugs = array_values(array_filter(explode('/', $parts['path'])));
      $lang = count($slugs) > 0 ? $slugs[0] : null;
      $langSettings = null;
      foreach (LanguagesHelper::list() as $settings) {
        if ($settings->node('slug')->get() === $lang) {
          $langSettings = $settings;
        }
      }

      // prepend current language, if path doesn't begin with a valid lang
      if ($langSettings === null) {
        $parts['path'] = rtrim(self::$slugs['lang'] . '/' . implode('/', $slugs), '/');
      }

      // rewrite simple lang-path like /en to /en/home
      else if (count($slugs) === 1) {
        $parts['path'] = '/' . $lang . '/' . $langSettings->node('homeslug')->get();
      }
    }
    $res = [
      'type' => 'page',
      'href' => self::buildPath($parts)
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    if ($blank) {
      $res['target'] = '_blank';
    }
    return $res;
  }

  /**
   * Get telephone link.
   */
  public static function getTel(string $tel, ?string $title = null): array
  {
    $tel = preg_replace('/^[+]{1,}/', '00', $tel);
    $tel = preg_replace('/[^0-9]/', '', $tel);
    $res = [
      'type' => 'tel',
      'href' => 'tel:' . $tel
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    return $res;
  }

  /**
   * Get extern link.
   */
  public static function getUrl(string $href, ?string $title = null, bool $blank = false): array
  {
    $url = self::parseUrl($href);
    $res = [
      'type' => 'url',
      'href' => $href,
      'host' => isset($url['host']) ? $url['host'] : '',
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    if ($blank) {
      $res['target'] = '_blank';
    }
    return $res;
  }

  /**
   * Compare two hosts and ports.
   */
  private static function isInternLink(array $parts, array $compare): bool
  {
    $host = isset($parts['host']) ? $parts['host'] : null;
    if ($host === null) {
      return true;
    }
    $hostCompare = isset($compare['host']) ? $compare['host'] : null;
    if ($host !== $hostCompare) {
      return false;
    }
    $port = isset($parts['port']) ? $parts['port'] : null;
    $portCompare = isset($compare['port']) ? $compare['port'] : null;
    return $port === $portCompare;
  }

  /**
   * Parsing url in parts.
   */
  public static function parseUrl(string $href): array
  {
    $parts = parse_url($href);

    // doing some normalization
    if (isset($parts['scheme'])) {
      $parts['scheme'] = strtolower($parts['scheme']);
    }
    if (isset($parts['host'])) {
      $parts['host'] = strtolower($parts['host']);
    }
    if (isset($parts['port'])) {
      $parts['port'] = (int) $parts['port'];
    }
    if (isset($parts['path'])) {
      $parts['path'] = trim(strtolower($parts['path']), '/');
      if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['mailto', 'tel'])) {
        $parts['path'] = '/' . $parts['path'];
      }
    }
    if (isset($parts['fragment'])) {
      if (strpos($parts['fragment'], '?') === false) {
        $hash = $parts['fragment'];
        $query = null;
      } else {
        $hash = substr($parts['fragment'], 0, strpos($parts['fragment'], '?') - 1);
        $query = substr($parts['fragment'], strpos($parts['fragment'], '?'));
      }
      if (!empty($hash)) {
        $parts['hash'] = $hash;
      }
      if (!empty($query)) {
        $parts['query'] = $query;
      }
      unset($parts['fragment']);
    }
    return $parts;
  }
}
