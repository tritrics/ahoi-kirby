<?php

use Kirby\Exception\Exception;
use Tritrics\AflevereApi\v1\Controllers\ApiController;
use Tritrics\AflevereApi\v1\Services\ImageService;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Services\ApiService;

kirby()::plugin(ApiService::$pluginName, [
  'options' => [
    'enabled' => [
      'info' => false,
      'node' => false,
      'nodes' => false,
      'form' => false // hidden, not documented so far
    ],
    'slug' => 'public-api',
    'field-name-separator' => '_',
  ],
  'hooks' => [
    'page.create:before' => function ($page, array $input) {
      if (ApiService::isProtectedSlug($input['slug'])) {
        throw new Exception('Slug not allowed');
      }
    },
    'page.changeSlug:before' => function ($page, string $slug, ?string $languageCode = null) {
      if (ApiService::isProtectedSlug($slug)) {
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
    $slug = ApiService::getApiSlug();
    if (!$slug) {
      return [];
    }
    $multilang = LanguagesService::isMultilang();
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
    }

    // expose
    if (ApiService::isEnabledInfo()) {
      $routes[] = [
        'pattern' => $slug . '/info',
        'method' => 'GET',
        'action' => function () {
          $controller = new ApiController();
          return $controller->info();
        }
      ];
    }

    // a language
    if (ApiService::isEnabledNode()) {
      $routes[] = [
        'pattern' => $slug . '/language/(:any)',
        'method' => 'GET',
        'action' => function ($resource = '') use ($multilang) {
          $controller = new ApiController();
          return $controller->language($resource);
        }
      ];
    }

    // a node
    if (ApiService::isEnabledNode()) {
      $routes[] = [
        'pattern' => $slug . '/node/(:all?)',
        'method' => 'GET|POST|OPTIONS',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $path) = ApiService::parsePath($resource, $multilang);
          $controller = new ApiController();
          return $controller->node($lang, $path);
        }
      ];
    }

    // children of a node
    if (ApiService::isEnabledNodes()) {
      $routes[] = [
        'pattern' => $slug . '/nodes/(:all?)',
        'method' => 'GET|POST|OPTIONS',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $path) = ApiService::parsePath($resource, $multilang);
          $controller = new ApiController();
          return $controller->nodes($lang, $path);
        }
      ];
    }

    // form handling
    if (ApiService::isEnabledForm()) {
      $routes[] = [
        'pattern' => $slug . '/form/(:all?)',
        'method' => 'POST|OPTIONS',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $action) = ApiService::parsePath($resource, $multilang);
          $controller = new ApiController();
          return $controller->form($lang, $action);
        }
      ];
    }
    return $routes;
  }
]);
