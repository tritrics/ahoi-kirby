<?php

namespace Tritrics\AflevereApi\v1\Helper;

use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Language;
use Kirby\Cms\Languages;
use Kirby\Exception\LogicException;

/**
 * Functions which get data from Kirby.
 */
class KirbyHelper
{
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

  public static function findPage(?string $slug): Page
  {
    return kirby()->site()->find($slug);
  }

  /**
   * Helper: Find a page by translated slug
   * (Kirby can only find by default slug)
   */
  public static function findPageBySlug(?string $lang, string $slug): Page
  {
    if (ConfigHelper::isMultilang()) {
      $pages = kirby()->site()->pages();
      $keys = explode('/', trim($slug, '/'));
      return self::findPageBySlugRec($pages, $lang, $keys);
    } else {
      return page($slug);
    }
  }

  /**
   * Subfunction of findPageBySlug.
   */
  private static function findPageBySlugRec(Pages $collection, ?string $lang, array $keys): ?Page
  {
    $key = array_shift($keys);
    foreach ($collection as $page) {
      if ($page->slug($lang) === $key) {
        if (count($keys) > 0) {
          return self::findPageBySlugRec($page->children(), $lang, $keys);
        } else {
          return $page;
        }
      }
    }
    return null;
  }

  public static function createPage (array $params, string $status = 'draft'): Page
  {
    $status = in_array($status, ['draft', 'listed', 'unlisted']) ? $status : 'draft';
    return kirby()->impersonate(
      'kirby',
      function () use ($params, $status) {
        $page = Page::create($params);
        $page->changeStatus($status);
        return $page;
      }
    );
  }

  public static function deletePage(Page $page): void
  {
    kirby()->impersonate(
      'kirby',
      function () use ($page) {
        $page->delete();
      }
    );
  }
}
