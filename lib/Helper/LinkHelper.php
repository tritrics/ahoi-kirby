<?php

namespace Tritrics\Ahoi\v1\Helper;

use Kirby\Cms\File;
use Kirby\Cms\Page;

class LinkHelper
{
  /**
   * Add global properties to attributes.
   */
  private static function addGlobals(array $res, ?string $title, ?bool $blank): array
  {
    if (!empty($title)) {
      $res['title'] = $title;
    }
    if ($blank) {
      $res['target'] = '_blank';
    }
    return $res;
  }

  /**
   * Get attributes for link.
   */
  public static function get(
    string|Page|File|null $mixed = null,
    string|null $title = null,
    bool $blank = false,
    string|null $lang = null,
    string $type = null
  ): array {
    $type = is_string($type) ? $type : self::getType($mixed);
    switch($type) {
      case 'anchor':
        return self::getAnchor($mixed, $title, $blank);
      case 'email':
        return self::getEmail($mixed, $title, $blank);
      case 'file':
        return self::getFile($mixed, $title, $blank);
      case 'page':
        return self::getPage($mixed, $title, $blank, $lang);
      case 'tel':
        return self::getTel($mixed, $title, $blank);
      case 'url':
        return self::getUrl($mixed, $title, $blank);
      default:
        return self::getCustom($mixed, $title, $blank);
    }
  }

  /**
   * Get attributes for anchor link.
   */
  public static function getAnchor(?string $href, ?string $title, ?bool $blank): array
  {
    $res = [
      'type' => 'anchor',
      'href' => ''
    ];
    if (is_string($href)) {
      $res['href'] = str_starts_with($href, '#') ? $href : '#' . $href;
    }
    return self::addGlobals($res, $title, $blank);
  }

  /**
   * Get attributes for custom link.
   */
  public static function getCustom(?string $href, ?string $title, ?bool $blank): array
  {
    $res = [
      'type' => 'custom',
      'href' => ''
    ];
    if (is_string($href)) {
      $res['href'] = $href;
    }
    return self::addGlobals($res, $title, $blank);
  }

  /**
   * Get attributes for email link.
   */
  public static function getEmail(?string $href, ?string $title, ?bool $blank): array
  {
    $res = [
      'type' => 'email',
      'href' => ''
    ];
    if (is_string($href)) {
      $res['href'] = str_starts_with($href, 'mailto:') ? $href : 'mailto:' . $href;
    }
    return self::addGlobals($res, $title, $blank);
  }

  /**
   * Get attributes for file link.
   * - file://Lqjjl2pjBbwOlpc9
   * - /@/file/Lqjjl2pjBbwOlpc9
   */
  public static function getFile(string|File $mixed, ?string $title, ?bool $blank): array
  {
    $res = [
      'type' => 'file',
      'href' => ''
    ];
    if (is_string($mixed)) {
      $mixed = KirbyHelper::findFileByKirbyLink($mixed);
    }
    if ($mixed instanceof File && AccessHelper::isAllowedModel($mixed)) {
      $href = $mixed->url();
      $res['href'] = $href;
    }
    return self::addGlobals($res, $title, $blank);
  }

  /**
   * Get attributes for page link.
   * - page://Xi1zACL5PCI93IIy
   * - /@/page/Xi1zACL5PCI93IIy
   * - [/lang]/some/path is NOT interpreted as intern link > custom
   * - an invalid intern link => '/[lang]'
   */
  public static function getPage(
    string|Page|null $mixed,
    ?string $title,
    ?bool $blank,
    ?string $lang
  ): array {
    $res = [
      'type' => 'page',
      'href' => ''
    ];
    if (is_string($mixed)) {
      $mixed = KirbyHelper::findPageByKirbyLink($mixed);
    }
    if ($mixed instanceof Page && AccessHelper::isAllowedModel($mixed)) {
      $href = $mixed->url($lang);
      $res['href'] = UrlHelper::getPath($href);
    }
    return self::addGlobals($res, $title, $blank);
  }

  /**
   * Get attributes for tel link.
   */
  public static function getTel(?string $href, ?string $title, ?bool $blank): array
  {
    $res = [
      'type' => 'tel',
      'href' => ''
    ];
    if (is_string($href)) {
      $tel = preg_replace('/^[+]{1,}/', '00', $href);
      $res['href'] = preg_replace('/[^0-9]/', '', $tel);
    }
    return self::addGlobals($res, $title, $blank);
  }

  /**
   * Get link type
   */
  public static function getType(?string $href)
  {
    if (str_starts_with($href, '#')) {
      return 'anchor';
    } else if (str_starts_with($href, 'mailto:')) {
      return 'email';
    } else if (str_starts_with($href, 'file://') || str_starts_with($href, '/@/file/')) {
      return 'file';
    } else if (str_starts_with($href, 'page://') || str_starts_with($href, '/@/page/')) {
      return 'page';
    } else if (str_starts_with($href, 'tel:')) {
      return 'tel';
    } else if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
      return 'url';
    }
    return 'custom';
  }

  /**
   * Get attributes for url link.
   */
  public static function getUrl(?string $href, ?string $title, ?bool $blank): array
  {
    $res = [
      'type' => 'url',
      'href' => $href,
    ];
    return self::addGlobals($res, $title, $blank);
  }
}