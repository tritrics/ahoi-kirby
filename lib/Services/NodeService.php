<?php

namespace Tritrics\Api\Services;

use Kirby\Cms\Site;
use Tritrics\Api\Data\Collection;
use Tritrics\Api\Models\PageModel;
use Tritrics\Api\Models\SiteModel;
use Tritrics\Api\Services\ApiService;
use Tritrics\Api\Services\RequestService;
use Tritrics\Api\Services\BlueprintService;
use Tritrics\Api\Services\FieldService;
/**
 * 
 */
class NodeService
{
  /**
   * Main method for action api/node/[id]
   * Gets a page or the site
   * 
   * @param $node page or site
   * @param Array $params
   * @return Array
   */
  public static function node ($node, $lang, $fields)
  {
    $blueprint = BlueprintService::getBlueprint($node);
    $res = ApiService::initResponse();
    if ($node instanceof Site) {
      $body = new SiteModel($node, $blueprint, $lang);
    } else {
      $body = new PageModel($node, $blueprint, $lang, true);
    }

    if ($fields === 'all' || (is_array($fields) && count($fields) > 0)) {
      $value = new Collection();
      FieldService::addFields(
        $value,
        $node->content($lang)->fields(),
        $blueprint->node('fields'),
        $lang,
        $fields
      );
      if ($value->count() > 0) {
        $body->add('value', $value);
      }
    }
    $res->add('body', $body);
    return $res->get();
  }

  /**
   * Main method for action api/children/[id]
   *
   * @param Kirby\Cms\Page $page
   * @param Array $params
   * @return Array
   */
  public static function children ($page, $lang, $params)
  {
    if (empty($page)) {
      return [];
    }
    if (is_array($params['filter'])) {
      $children = RequestService::filterChildren($page, $params['filter'], $lang);
    } else {
      $children = $page->children();
    }

    // Limit, paging, sorting
    if ($params['order'] === 'desc') {
      $children = $children->flip();
    }

    $head = [];
    $abscount = $children->count();
    if ($params['limit'] > 0 && $abscount > 0) {

      // Pagination
      $pagecount = ceil($abscount / $params['limit']);
      $pagenum = $params['page'] <= $pagecount ? $params['page'] : $pagecount;
      $offset = ($pagenum - 1) * $params['limit'];
      $children = $children->slice($offset, $params['limit']);

      $head['page'] = $pagenum;
      $head['limit'] = $params['limit'];
      $head['abscount'] = $abscount;
      $head['pagecount'] = $pagecount;
      $head['rangestart'] = $offset + 1;
      $head['rangeend'] = $offset + $children->count();
      $head['rangecount'] = $children->count();
    } else {
      $head['page'] = $abscount > 0 ? 1 : 0;
      $head['limit'] = $params['limit'];
      $head['abscount'] = $abscount;
      $head['pagecount'] = $abscount > 0 ? 1 : 0;
      $head['rangestart'] = $abscount > 0 ? 1 : 0;
      $head['rangeend'] = $abscount;
      $head['rangecount'] = $abscount;
    }

    $res = ApiService::initResponse();
    $res->add('head', $head);
    $res->add('children', self::getChildren($children, $lang, ['listed'], $params['fields']));
    return $res->get();
  }

  /**
   * get children filtered by status
   *
   * @param Kirby\Cms\Pages $children
   * @param Array $status, [ listed, unlisted, draft ]
   * @return Collection
   */
  private static function getChildren ($children, $lang, $status, $fields)
  {
    $res = new Collection();
    foreach ($children as $child) {
      if (!in_array($child->status(), $status)) {
        continue;
      }

      $blueprint = BlueprintService::getBlueprint($child);
      $node = new Collection();
      $node->add('head', new PageModel($child, $blueprint, $lang, false));
      if ($fields === 'all' || (is_array($fields) && count($fields) > 0)) {
        $content = new Collection();
        FieldService::addFields(
          $content,
          $child->content($lang)->fields(),
          $blueprint->node('fields'),
          $lang,
          $fields
        );
        if ($content->count() > 0) {
          $node->add('content', $content);
        }
      }
      $res->push($node);
    }
    return $res;
  }
}
