<?php

namespace Tritrics\AflevereApi\v1\Helper;

use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Helper\GlobalHelper;

/**
 * Service for any kind of links (texts, page, file, user) to produce consistant output.
 */
class LinkHelper
{
  /**
   * 2-digit language code
   * 
   * @var String
   */
  private static $lang;


  /**
   * Detected slugs with starting slash.
   * 
   * @var Array
   */
  private static $slugs = [
    'home' => null, // /home
    'media' => '', // /media, (string because strlen())
    'lang' => null // /en, if multilang-site
  ];

  /**
   * Detects the linktype from a given $href.
   * 
   * @param String $lang 
   * @param String $href
   * @param String $title
   * @param Boolean $target
   * @return Array 
   */
  public static function getInline($lang, $href, $title = null, $target = false)
  {
    // Initialization
    // do only once
    if (self::$lang !== $lang) {
      self::$lang = $lang;
      $home = GlobalHelper::parseUrl(site()->homePage()->uri(self::$lang));
      self::$slugs['home'] = $home['path'];
      $media = GlobalHelper::parseUrl(kirby()->url('media'));
      self::$slugs['media'] = $media['path'];
      self::$slugs['lang'] = '/' . LanguagesService::getSlug(self::$lang);
    }
    $hosts = GlobalHelper::getHosts(self::$lang);

    // rewrite intern page and file links, which start with
    // /@/page and /@/file
    if (str_starts_with($href, '/@/page/')) {
      $uuid = str_replace('/@/page/', 'page://', $href);
      $page = kirby()->page($uuid);
      if ($page->exists()) {
        $href = $page->url();
      } else {
        return;
      }
    } else if (str_starts_with($href, '/@/file/')) {
      $uuid = str_replace('/@/file/', 'file://', $href);
      $file = kirby()->file($uuid);
      if ($file->exists()) {
        $href = $file->url();
      } else {
        return;
      }
    }

    // working with splitted url
    $parts = GlobalHelper::parseUrl($href);

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
        return self::getFile(GlobalHelper::buildUrl($parts), $title, $target);
      } 
    }

    // intern links
    // use buildPath() -> make intern
    if (self::isInternLink($parts, $hosts['referer']) || self::isInternLink($parts, $hosts['referer'])) {
      return self::getPage(self::buildPath($parts), $title, $target);   
    }

    // all other links
    return self::getUrl(GlobalHelper::buildUrl($parts), $title, $target);
  }

  /**
   * Get extern link.
   * 
   * @param String $lang 
   * @param Mixed $href 
   * @param String $title 
   * @return Array 
   */
  public static function getUrl($href, $title = null, $blank = false)
  {
    $url = GlobalHelper::parseUrl($href);
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
   * Get page (intern) link.
   * 
   * @param String $lang 
   * @param String $path 
   * @param String $title 
   * @return Array 
   */
  public static function getPage($path, $title = null, $blank = false)
  {
    // check and correct links to home page(s)
    $parts = GlobalHelper::parseUrl($path);

    // path is empty, set path to homepage, optional with prepending lang
    if (!isset($parts['path']) || empty($parts['path']) || $parts['path'] === '/') {
      if (LanguagesService::isMultilang()) {
        $parts['path'] = self::$slugs['lang'] . self::$slugs['home'];
      } else {
        $parts['path'] = self::$slugs['home'];
      }
    }
    
    // path is not empty in a multilang installation
    else if (LanguagesService::isMultilang()) {
      $slugs = array_values(array_filter(explode('/', $parts['path'])));
      $lang = count($slugs) > 0 ? $slugs[0] : null;
      $langSettings = null;
      foreach (LanguagesService::list() as $settings) {
        if($settings->node('slug')->get() === $lang) {
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
   * Get file (download) link.
   * 
   * @param String $lang 
   * @param String $path 
   * @param String $title 
   * @return Array 
   */
  public static function getFile($path, $title = null, $blank = false)
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
   * Get email link.
   * 
   * @param String $lang 
   * @param Mixed $email 
   * @param String $title 
   * @return Array
   */
  public static function getEmail($email, $title = null)
  {
    $res = [
      'type' => 'email',
      'href' => 'mailto:' . $email
    ];
    if(!empty($title)) {
      $res['title'] = $title;
    }
    return $res;
  }

  /**
   * Get telephone link.
   * 
   * @param String $lang 
   * @param Mixed $tel 
   * @param String $title 
   * @return Array
   */
  public static function getTel($tel, $title = null)
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
   * Get anchor.
   * 
   * @param String $lang 
   * @param Mixed $anchor 
   * @param String $title 
   * @return Array
   */
  public static function getAnchor($anchor, $title = null)
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
   * 
   * @param String $lang
   * @param String $title 
   * @return Array
   */
  public static function getCustom($link, $title = null)
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
   * Build the path, ignoring all host-parts.
   * 
   * @param Array $parts 
   * @return String 
   */
  private static function buildPath($parts)
  {
    return
      (isset($parts['path']) ? "{$parts['path']}" : '') .
      (isset($parts['hash']) ? "#{$parts['hash']}" : '') .
      (isset($parts['query']) ? "?{$parts['query']}" : '');
  }

  /**
   * Compare two hosts and ports.
   * 
   * @param Array $parts 
   * @param Array $compare 
   * @return Boolean 
   */
  private static function isInternLink($parts, $compare)
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
}
