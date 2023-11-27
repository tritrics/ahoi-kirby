<?php

namespace Tritrics\Api;

use Kirby\Exception\Exception;
use Tritrics\Api\Controllers\ApiController;
use Tritrics\Api\Services\ImageService;
use Tritrics\Api\Services\LanguageService;
use Tritrics\Api\Services\RouteService;

kirby()::plugin('tritrics/aflevere-api', [
  'options' => [
    'enabled' => [
      'languages' => false,
      'node' => false,
      'children' => false,
      'submit' => false
    ],
    'slug' => '/public-api',
    'field-name-separator' => '_'
  ],
  'hooks' => [
    'page.create:before' => function ($page, array $input) {
      if (RouteService::isProtectedSlug($input['slug'])) {
        throw new Exception('Slug not allowed');
      }
    },
    'page.changeSlug:before' => function ($page, string $slug, ?string $languageCode = null) {
      if (RouteService::isProtectedSlug($slug)) {
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
    if (!$slug) {
      return [];
    }
    $multilang = LanguageService::isMultilang();
    $routes = array();

    // language-based routes, only relevant if any language
    // exists in site/languages/
    if ($multilang) {

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

    // a node
    $routes[] = [
      'pattern' => $slug . '/node/(:all?)',
      'method' => 'GET|POST|OPTIONS',
      'action' => function ($path = '') use ($multilang) {
        $controller = new ApiController();
        list($lang, $slug) = RouteService::parsePath($path, $multilang);
        return $controller->node($lang, $slug);
      }
    ];

    // children of a node
    $routes[] = [
      'pattern' => $slug . '/collection/(:all?)',
      'method' => 'GET|POST|OPTIONS',
      'action' => function ($path = '') use ($multilang) {
        $controller = new ApiController();
        list($lang, $slug) = RouteService::parsePath($path, $multilang);
        return $controller->collection($lang, $slug);
      }
    ];

    // post
    $routes[] = [
      'pattern' => $slug . ($multilang ? '/submit/(:any)/(:any)' : '/submit/(:any)'),
      'method' => 'POST|OPTIONS',
      'action' => function ($param1, $param2 = null) use ($multilang) {
        $controller = new ApiController();
        if ($multilang) {
          return $controller->submit($param1, $param2);
        } else {
          return $controller->submit(null, $param1);
        }
      }
    ];
    return $routes;
  }
]);
