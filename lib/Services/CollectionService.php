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
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;
use Tritrics\Ahoi\v1\Helper\FieldHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;
use Tritrics\Ahoi\v1\Helper\AccessHelper;
use Tritrics\Ahoi\v1\Models\SiteModel;

/**
 * Service for API's pages interface. Handles a collection of pages.
 */
class CollectionService
{
  /**
   * Get pages or files as childs from model.
   * $params = [
   *   'fields',
   *   'filter',
   *   'limit',
   *   'offset',
   *   'status',
   *   'sort',
   * ];
   */
  public static function get(string $request, Page|Site $model, ?string $lang, array $params): Collection
  {
    $blueprint = BlueprintHelper::get($model);

    if ($model instanceof Page) {
      $body = new PageModel($model, $blueprint, $lang, [], true);
    } else if ($model instanceof Site) {
      $body = new SiteModel($model, $blueprint, $lang, [], true);
    }

    // request children of pages or files
    if ($request === 'pages') {
      $children = KirbyHelper::getPages($model, $params);
    } else if ($request === 'files') {
      $children = KirbyHelper::getFiles($model, $params);
    } else {
      return $body;
    }

    // add collection info
    $collection = $body->add('collection');
    $collection->add('total', $children->count());
    $collection->add('limit', $params['limit']);
    $collection->add('offset', $params['offset']);

    // adding children to value
    $body->add('entries', self::getChildren($request, $children, $lang, $params['fields']));
    return $body;
  }

  /**
   * Get children
   */
  private static function getChildren(
    string $request,
    Pages|Files $children,
    ?string $lang,
    ?array $fields
  ): Collection {
    $res = new Collection();
    foreach ($children as $child) {
      if ($request === 'pages') {
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
