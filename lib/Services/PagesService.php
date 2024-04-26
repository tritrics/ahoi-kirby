<?php

namespace Tritrics\Tric\v1\Services;

use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Site;
use Kirby\Exception\InvalidArgumentException;
use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Models\PageModel;
use Tritrics\Tric\v1\Helper\ResponseHelper;
use Tritrics\Tric\v1\Helper\FilterHelper;
use Tritrics\Tric\v1\Helper\BlueprintHelper;
use Tritrics\Tric\v1\Helper\FieldHelper;
use Tritrics\Tric\v1\Helper\UrlHelper;
use Tritrics\Tric\v1\Helper\LinkHelper;

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
  public static function get(Page|Site $model, ?string $lang, array $params): array
  {
    $blueprint = BlueprintHelper::getBlueprint($model);
    $res = ResponseHelper::getHeader();
    $body = new PageModel($model, $blueprint, $lang, true);

    // request children
    if (count($params['filter']) > 0) {
      $children = FilterHelper::filterChildren($model, $params['filter']);
    } else {
      $children = $model->children();
    }

    // Limit, paging, sorting
    if ($params['order'] === 'desc') {
      $children = $children->flip();
    }

    // add meta info about children
    $body->node('type')->set('pages');
    $meta = $body->node('meta');
    $abscount = $children->count();
    if ($params['limit'] > 0 && $abscount > 0) {
      $setcount = ceil($abscount / $params['limit']);
      $pagenum = $params['set'] <= $setcount ? $params['set'] : $setcount;
      $offset = ($pagenum - 1) * $params['limit'];
      $children = $children->slice($offset, $params['limit']);
      $meta->add('pages', $pagenum);
      $meta->add('limit', $params['limit']);
      $meta->add('abscount', $abscount);
      $meta->add('setscount', $setcount);
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

    // adding children to value
    $body->add('value', self::getChildren($children, $lang, [ 'listed' ], $params['fields']));

    // add body
    $res->add('body', $body);
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
      $model = new PageModel($child, $blueprint, $lang, false);

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
          $model->add('value', $value);
        }
      }
      $res->push($model);
    }
    return $res;
  }
}
