<?php

namespace Tritrics\Api;

use Kirby\Exception\Exception;
use Tritrics\Api\Controllers\ApiController;
use Tritrics\Api\Services\ImageService;
use Tritrics\Api\Services\RouteService;

kirby()::plugin('tritrics/restapi', [
  'options' => [
    'enabled' => [
      'languages' => false,
      'site' => false,
      'node' => false,
      'children' => false,
      'submit' => false
    ],
    'slug' => '/rest-api',
    'field-name-separator' => '_'
  ],
  'hooks' => [
    'page.create:before' => function ($page, array $input) {
      if(RouteService::isProtectedSlug($input['slug'])) {
        throw new Exception('Slug not allowed');
      }
    },
    'page.changeSlug:before' => function ($page, string $slug, ?string $languageCode = null) {
      if(RouteService::isProtectedSlug($slug)) {
        throw new Exception('Slug not allowed');
      }
    },
    'route:after' => function ($route, $path, $method, $result) {
      $attributes = $route->attributes();
      if (isset($attributes['env']) && $attributes['env'] === 'media' && empty($result)) {
        ImageService::get($path, $route->arguments(), $route->pattern());
      }
      return;
    }
  ],
  'routes' => function ($kirby) {
    $slug = RouteService::getApiSlug();
    if ( ! $slug) {
      return [];
    }
    $routes = array();

    // language-based routes, only relevant if any language
    // exists in site/languages/
    if ($kirby->defaultLanguage()) {

      // default kirby route must be overwritten to prevent kirby
      // from redirecting to default language. This is done by
      // the frontend.
      $routes[] = [
        'pattern' => '',
        'method'  => 'ALL',
        'env'     => 'site',
        'action'  => function () use ($kirby) {
          return $kirby->defaultLanguage()->router()->call();
        }
      ];
      $routes[] = [
        'pattern' => $slug . '/languages',
        'method' => 'GET|POST|OPTIONS',
        'action' => function () {
          $controller = new ApiController();
          return $controller->languages();
        }
      ];
    }

    // routes for plugin requests site | node | children
    $routes[] = [
      'pattern' => $slug . '/site/(:any)',
      'method' => 'GET|POST|OPTIONS',
      'action' => function ($lang) {
        $controller = new ApiController();
        return $controller->site($lang);
      }
    ];
    $routes[] = [
      'pattern' => $slug . '/node/(:any)/(:all)',
      'method' => 'GET|POST|OPTIONS',
      'action' => function ($lang, $id) {
        $controller = new ApiController();
        return $controller->node($lang, $id);
      }
    ];
    $routes[] = [
      'pattern' => $slug . '/children/(:any)/(:all)',
      'method' => 'GET|POST|OPTIONS',
      'action' => function ($lang, $id) {
        $controller = new ApiController();
        return $controller->children($lang, $id);
      }
    ];
    $routes[] = [
      'pattern' => $slug . '/submit/(:any)/(:any)',
      'method' => 'POST|OPTIONS',
        'action' => function ($lang, $action) {
          $controller = new ApiController();
          return $controller->submit($lang, $action);
        }
    ];
    return $routes;
  }
]);