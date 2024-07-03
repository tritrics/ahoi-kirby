<?php

namespace Tritrics\Ahoi\v1\Services;

use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Files;
use Kirby\Cms\Site;
use Kirby\Exception\InvalidArgumentException;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Models\PageModel;
use Tritrics\Ahoi\v1\Models\FileModel;
use Tritrics\Ahoi\v1\Helper\FilterHelper;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;
use Tritrics\Ahoi\v1\Helper\FieldHelper;

/**
 * Service for API's pages interface. Handles a collection of pages.
 */
class CollectionService
{
  /**
   * Get pages or files as childs from model.
   */
  public static function get(string $request, Page|Site $model, ?string $lang, array $params): Collection
  {
    $blueprint = BlueprintHelper::get($model);
    $body = new PageModel($model, $blueprint, $lang, [], true);

    // request children
    if ($request === 'pages') {
      if (count($params['filter']) > 0) {
        $children = FilterHelper::filterChildren($model, $params['filter']);
      } else {
        $children = $model->children();
      }
    } else {
      $children = $model->files();
    }

    // Limit, paging, sorting
    if ($params['order'] === 'desc') {
      $children = $children->flip();
    }

    // add collection info
    $collection = $body->add('collection');
    $abscount = $children->count();
    if ($params['limit'] > 0 && $abscount > 0) {
      $setcount = ceil($abscount / $params['limit']);
      $set = $params['set'] <= $setcount ? $params['set'] : $setcount;
      $offset = ($set - 1) * $params['limit'];
      $children = $children->slice($offset, $params['limit']);
      $collection->add('set', $set);
      $collection->add('limit', $params['limit']);
      $collection->add('count', $children->count());
      $collection->add('start', $offset + 1);
      $collection->add('end', $offset + $children->count());
      $collection->add('sets', $setcount);
      $collection->add('total', $abscount);
    } else {
      $collection->add('set', $abscount > 0 ? 1 : 0);
      $collection->add('limit', $params['limit']);
      $collection->add('count', $abscount);
      $collection->add('start', $abscount > 0 ? 1 : 0);
      $collection->add('end', $abscount);
      $collection->add('sets', $abscount > 0 ? 1 : 0);
      $collection->add('total', $abscount);
    }

    // adding children to value
    $body->add('entries', self::getChildren($request, $children, $lang, [ 'listed' ], $params['fields']));
    return $body;
  }

  /**
   * Get children filtered by status.
   * 
   * @throws InvalidArgumentException 
   */
  private static function getChildren(
    string $request,
    Pages|Files $children,
    ?string $lang,
    array $status,
    string|array $fields
  ): Collection {
    $res = new Collection();
    foreach ($children as $child) {
      if ($request === 'pages') {
        if (!in_array($child->status(), $status)) {
          continue;
        }
        $blueprint = BlueprintHelper::get($child);
        $model = new PageModel($child, $blueprint, $lang);
      } else {
        $blueprint = BlueprintHelper::get($child);
        $model = new FileModel($child, $blueprint, $lang);
      }
      if (is_array($fields) && count($fields) > 0) {
        $value = new Collection();
        FieldHelper::addFields(
          $value,
          $child->content($lang)->fields(),
          $blueprint->node('fields'),
          $lang,
          $fields
        );
        if ($value->count() > 0) {
          $model->add('fields', $value);
        }
      }
      $res->push($model);
    }
    return $res;
  }
}
