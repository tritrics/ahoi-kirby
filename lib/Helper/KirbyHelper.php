<?php

namespace Tritrics\Tric\v1\Helper;

use Exception;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Cms\Pages;
use Kirby\Cms\Language;
use Kirby\Cms\Languages;
use Kirby\Exception\LogicException;
use Throwable;

/**
 * Functions which get data from Kirby.
 */
class KirbyHelper
{
  /**
   * Change status of a given page.
   * 
   * @throws Throwable 
   */
  public static function changeStatus (Page $page, string $status): Page
  {
    $status = in_array($status, ['draft', 'listed', 'unlisted']) ? $status : 'draft';
    return kirby()->impersonate(
      'kirby',
      function () use ($page, $status) {
        return $page->changeStatus($status);
      }
    );
  }

  /**
   * Create a Page.
   */
  public static function createPage(array $params): Page
  {
    return kirby()->impersonate(
      'kirby',
      function () use ($params) {
        return Page::create($params);
      }
    );
  }

  /**
   * Delete a given Page.
   */
  public static function deletePage(Page $page): void
  {
    kirby()->impersonate(
      'kirby',
      function () use ($page) {
        $page->delete();
      }
    );
  }

  /**
   * Find a file by Kirby's intern link like /@/file/uuid.
   */
  public static function findFileByKirbyLink(?string $href = null): ?File
  {
    if (is_string($href)) {
      $uuid = str_replace('/@/file/', 'file://', $href);
      $file = kirby()->file($uuid);
      if ($file && $file->exists()) {
        return $file;
      }
    }
    return null;
  }

  /**
   * Find a page by Kirby's intern link like /@/page/uuid.
   */
  public static function findPageByKirbyLink(?string $href = null): ?Page
  {
    if (is_string($href)) {
      $uuid = str_replace('/@/page/', 'page://', $href);
      $page = kirby()->page($uuid);
      if ($page && $page->exists() && !$page->isDraft()) {
        return $page;
      }
    }
    return null;
  }

  /**
   * Find a page by default slug.
   */
  public static function findPage(string|null $slug = null): ?Page
  {
    if (is_string($slug)) {
      $page = kirby()->site()->find($slug);
      if($page && $page->exists() && !$page->isDraft()) {
        return $page;
      }
    }
    return null;
  }

  /**
   * Helper: Find a page by translated slug
   * (Kirby can only find by default slug)
   */
  public static function findPageBySlug(?string $lang = null, ?string $slug = null): ?Page
  {
    if (!is_string($slug)) {
      return null;
    }

    // search by default slug, the same for multilang or singlelang
    $res = self::findPage($slug);
    if ($res) {
      return $res;
    }

    // If page is not found by default slug, try translated slug
    if (ConfigHelper::isMultilang()) {
      $pages = kirby()->site()->pages();
      $keys = explode('/', trim($slug, '/'));
      return self::findPageBySlugRec($pages, $lang, $keys);
    }
    return null;
  }

  /**
   * Subfunction of findPageBySlug.
   */
  private static function findPageBySlugRec(Pages $collection, ?string $lang, array $keys): ?Page
  {
    $key = array_shift($keys);
    foreach ($collection as $page) {
      if (!$page->isDraft() && $page->slug($lang) === $key) {
        if (count($keys) > 0) {
          return self::findPageBySlugRec($page->children(), $lang, $keys);
        } else {
          return $page;
        }
      }
    }
    return null;
  }

  /**
   * Get url of parent model
   */
  public static function getParentUrl(Page|Site $model, ?string $lang): string
  {
    $langSlug = LanguagesHelper::getSlug($lang);
    $parent = $model->parent();
    if ($parent) {
      return '/' . ltrim($langSlug . '/' . $parent->uri($lang), '/');
    }
    return '/';
  }

  /**
   * Get a single language as Kirby object defined by $code.
   */
  public static function getLanguage(?string $code): ?Language
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
  public static function getLanguages(): ?Languages
  {
    try {
      return kirby()->languages();
    } catch (LogicException $E) {
      return null;
    }
  }
}
