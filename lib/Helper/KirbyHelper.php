<?php

namespace Tritrics\Ahoi\v1\Helper;

use Exception;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Cms\User;
use Kirby\Cms\Pages;
use Kirby\Cms\Files;
use Kirby\Exception\InvalidArgumentException;
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
   * Helper to filter Pages or Files
   */
  public static function filterCollection(Pages|Files $children, array $options = []): Pages|Files
  {
    $children = $children->filter(
      fn($child) => AccessHelper::isAllowedModel($child)
    );
    if (isset($options['filter']) && is_array($options['filter'])) {
      foreach ($options['filter'] as $args) {
        $children = $children->filterBy(...$args);
      }
    }
    if (isset($options['sort']) && is_array($options['sort'])) {
      $children = $children->sortBy(...$options['sort']);
    }
    if (isset($options['offset'])) {
      $children = $children->offset($options['offset']);
    }
    if (isset($options['limit'])) {
      $children = $children->limit($options['limit']);
    }
    return $children;
  }

  /**
   * Find a Page or a File by slug.
   */
  public static function findAny(?string $lang = null, ?string $slug = null): null|Site|Page|File
  {
    $node = self::findPage($lang, $slug);
    if ($node) {
      return $node;
    }
    return self::findFile($lang, $slug);
  }

  /**
   * Find a File by slug, language independant.
   */
  public static function findFile(?string $lang = null, ?string $slug = null): ?File
  {
    $path = UrlHelper::parse($slug);
    if (!is_string($path['dirname']) || !is_string($path['basename'])) {
      return null;
    }
    $node = self::findPage($lang, $path['dirname']);
    if (!$node) {
      return null;
    }
    return $node->files()->find($path['basename']);
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
   * Helper: Find a page by translated slug
   * (Kirby can only find by default slug)
   */
  public static function findPage(?string $lang = null, ?string $slug = null): Page|Site|null
  {
    if (!is_string($slug)) {
      return kirby()->site();
    }
    $slug = trim($slug, '/');

    // search by default slug, the same for multilang or singlelang
    $page = kirby()->site()->find($slug);
    if ($page && $page->exists() && !$page->isDraft()) {
      return $page;
    }

    // If page is not found by default slug, try translated slug
    if (ConfigHelper::isMultilang()) {
      $pages = kirby()->site()->pages();
      $keys = explode('/', $slug);
      return self::findPageRec($pages, $lang, $keys);
    }
    return null;
  }

  /**
   * Find a page by Kirby's intern link like /@/page/uuid.
   */
  public static function findPageByKirbyLink(?string $href = null): ?Page
  {
    try {
      if (is_string($href)) {
        $uuid = str_replace('/@/page/', 'page://', $href);
        $page = kirby()->page($uuid);
        if ($page && $page->exists() && !$page->isDraft()) {
          return $page;
        }
      }
    } catch (Exception $e) {}
    return null;
  }

  /**
   * Subfunction of findPageBySlug.
   */
  private static function findPageRec(Pages $collection, ?string $lang, array $keys): ?Page
  {
    $key = array_shift($keys);
    foreach ($collection as $page) {
      if (!$page->isDraft() && $page->slug($lang) === $key) {
        if (count($keys) > 0) {
          return self::findPageRec($page->children(), $lang, $keys);
        } else {
          return $page;
        }
      }
    }
    return null;
  }

  /**
   * Getting the blueprint of a model.
   */
  public static function getBlueprintPath(Page|Site|File|User $model): string|null
  {
    if ($model instanceof Page) {
      return 'pages/' . $model->intendedTemplate();
    } else if ($model instanceof Site) {
      return 'site';
    } else if ($model instanceof File) {
      return 'files/' . $model->template();
    } else if ($model instanceof User) {
      return 'users/' . $model->role();
    }
    return null;
  }

  /**
   * Getting pages/children of a page, optionally filtered.
   * It's not possible to return status = draft.
   */
  public static function getPages(Page|Site $model, array $options = []): Pages
  {
    $status = 'listed';
    if (isset($options['status']) && in_array($options['status'], ['listed', 'unlisted', 'published'])) {
      $status = $options['status'];
    }
    $children = $model->children()->$status();
    return self::filterCollection($children, $options);
  }

  /**
   * Getting files of a page, optionally filtered.
   */
  public static function getFiles(Page|Site $model, array $options = []): Files
  {
    return self::filterCollection($model->files(), $options);
  }
}
