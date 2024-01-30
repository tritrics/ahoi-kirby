<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Site;
use Kirby\Exception\InvalidArgumentException;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Models\PageModel;
use Tritrics\AflevereApi\v1\Helper\ResponseHelper;
use Tritrics\AflevereApi\v1\Helper\FilterHelper;
use Tritrics\AflevereApi\v1\Helper\BlueprintHelper;
use Tritrics\AflevereApi\v1\Helper\FieldHelper;

/**
 * Service for API's pages interface. Handles a collection of pages.
 */
class PagesService
{
  /**
   * Main method to respond to "pages" action.
   * 
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get(Page|Site $node, ?string $lang, array $params): array
  {
    if (empty($node)) {
      return [];
    }
    $blueprint = BlueprintHelper::getBlueprint($node);
    if (count($params['filter']) > 0) {
      $children = FilterHelper::filterChildren($node, $params['filter']);
    } else {
      $children = $node->children();
    }

    // Limit, paging, sorting
    if ($params['order'] === 'desc') {
      $children = $children->flip();
    }

    $res = ResponseHelper::getHeader();
    $body = $res->add('body');
    $body->add('type', 'nodes');
    $meta = $body->add('meta');

    if ($node instanceof Site) {
      $meta->add('parent', 'site');
      $meta->add('host', $node->url($lang));
    } else {
      $meta->add('parent', 'page');
      $meta->add('id', $node->id());
      $meta->add('slug', $node->slug($lang));
    }
    if ($lang !== null) {
      $meta->add('lang', $lang);
    }
    $abscount = $children->count();
    if ($params['limit'] > 0 && $abscount > 0) {
      $pagecount = ceil($abscount / $params['limit']);
      $pagenum = $params['page'] <= $pagecount ? $params['page'] : $pagecount;
      $offset = ($pagenum - 1) * $params['limit'];
      $children = $children->slice($offset, $params['limit']);
      $meta->add('pages', $pagenum);
      $meta->add('limit', $params['limit']);
      $meta->add('abscount', $abscount);
      $meta->add('pagescount', $pagecount);
      $meta->add('rangestart', $offset + 1);
      $meta->add('rangeend', $offset + $children->count());
      $meta->add('rangecount', $children->count());
    } else {
      $meta->add('pages', $abscount > 0 ? 1 : 0);
      $meta->add('limit', $params['limit']);
      $meta->add('abscount', $abscount);
      $meta->add('pagescount', $abscount > 0 ? 1 : 0);
      $meta->add('rangestart', $abscount > 0 ? 1 : 0);
      $meta->add('rangeend', $abscount);
      $meta->add('rangecount', $abscount);
    }

    if ($blueprint->has('api', 'meta')) {
      foreach($blueprint->node('api', 'meta')->get() as $key => $value) {
        if (!$meta->has($key)) {
          $meta->add($key, $value);
        }
      }
    }

    $body->add('value', self::getChildren($children, $lang, [ 'listed' ], $params['fields']));
    return $res->get();
  }

  /**
   * Get children filtered by status.
   * 
   * @throws InvalidArgumentException 
   */
  private static function getChildren(
    Pages $children,
    ?string $lang,
    array $status,
    string|array $fields
  ): Collection {
    $res = new Collection();
    foreach ($children as $child) {
      if (!in_array($child->status(), $status)) {
        continue;
      }

      $blueprint = BlueprintHelper::getBlueprint($child);
      $node = new PageModel($child, $blueprint, $lang, false);

      // don't deactivate "all" here, because it's required for one-pager
      if ($fields === 'all' || (is_array($fields) && count($fields) > 0)) {
        $value = new Collection();
        FieldHelper::addFields(
          $value,
          $child->content($lang)->fields(),
          $blueprint->node('fields'),
          $lang,
          $fields
        );
        if ($value->count() > 0) {
          $node->add('value', $value);
        }
      }
      $res->push($node);
    }
    return $res;
  }
}
