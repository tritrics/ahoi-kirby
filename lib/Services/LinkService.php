<?php

namespace Tritrics\Api\Services;

use Tritrics\Api\Services\LanguageService;

class LinkService
{
  /** */
  private static $lang;

  /** */
  private static $baseUrl;

  /** */
  private static $mediaUrl;

  /** */
  private static $homeSlug;

  /** */
  private static $langSlug;

  /**
   * Detects the linktype from a given $href
   * @param string $lang 
   * @param string $href
   * @param string $title
   * @return array 
   */
  public static function get($lang, $href, $title = null, $target = false)
  {
    if (self::$lang !== $lang) {
      self::$lang = $lang;
      $kirby = kirby();
      $site = site();
      self::$mediaUrl = rtrim($kirby->url('media'), '/');
      self::$homeSlug = $site->homePage()->uri(self::$lang);
      self::$langSlug = LanguageService::getSlug(self::$lang);
      self::$baseUrl = rtrim(rtrim($site->url(self::$lang), '/' . self::$langSlug), '/');
      // error_log('baseUrl: ' . self::$baseUrl);
      // error_log('mediaUrl: ' . self::$mediaUrl);
      // error_log('homeSlug: ' . self::$homeSlug);
      // error_log('langSlug: ' . self::$langSlug);
    }

    // file link, keep as it is
    if (substr($href, 0, strlen(self::$mediaUrl)) === self::$mediaUrl) {
      return self::getFile($href, $title, $target);
    }

    // page link starting with host
    else if (substr($href, 0, strlen(self::$baseUrl)) === self::$baseUrl) {
      $path = substr($href, strlen(self::$baseUrl));
      if ($path === '' . self::$langSlug || $path === '/' . self::$langSlug) {
        $path = '/' . ltrim(self::$langSlug . '/' . self::$homeSlug, '/');
      } else {
        $path = '/' . ltrim(self::$langSlug . $path, '/');
      }
      return self::getPage($path, $title, $target);
    }

    // mailto
    else if (substr($href, 0, 7) === 'mailto:') {
      return self::getEmail(substr($href, 7), $title);
    }

    // tel
    else if (substr($href, 0, 4) === 'tel:') {
      return self::getTel(substr($href, 4), $title);
    }

    // anchor
    else if (substr($href, 0, 1) === '#') {
      return self::getAnchor(substr($href, 1), $title);
    }

    // extern links
    else if (substr($href, 0, 7) === 'http://' || substr($href, 0, 8) === 'https://') {
      return self::getExtern($href, $title, $target);
    }

    // default extern with /path/to/page
    return self::getPage($href, $title, $target);
  }

  /**
   * get extern link
   * @param string $lang 
   * @param mixed $href 
   * @param string $title 
   * @return array 
   */
  public static function getExtern($href, $title = null, $blank = false)
  {
    $res = [
      'type' => 'extern',
      'href' => $href,
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
   * get page (intern) link
   * @param string $lang 
   * @param string $path 
   * @param string $title 
   * @return array 
   */
  public static function getPage($path, $title =null, $blank = false)
  {
    $res = [
      'type' => 'page',
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
   * get file (download) link
   * @param string $lang 
   * @param string $path 
   * @param string $title 
   * @return array 
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
   * get email link
   * @param string $lang 
   * @param mixed $email 
   * @param string $title 
   * @return array
   */
  public static function getEmail($email, $title = null)
  {
    $res = [
      'type' => 'email',
      'href' => 'mailto:' . $email
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    return $res;
  }

  /**
   * get telephone linke
   * @param string $lang 
   * @param mixed $tel 
   * @param string $title 
   * @return array
   */
  public static function getTel($tel, $title = null)
  {
    $res = [
      'type' => 'tel',
      'href' => 'tel:' . $tel,
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    return $res;
  }

  /**
   * get anchor
   * @param string $lang 
   * @param mixed $anchor 
   * @param string $title 
   * @return array
   */
  public static function getAnchor($anchor, $title = null)
  {
    $res = [
      'type' => 'anchor',
      'href' => '#' . $anchor
    ];
    if (!empty($title)) {
      $res['title'] = $title;
    }
    return $res;
  }
}
