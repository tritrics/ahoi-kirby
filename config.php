<?php

use Kirby\Exception\Exception;
use Tritrics\AflevereApi\v1\Controllers\GetController;
use Tritrics\AflevereApi\v1\Controllers\ActionController;
use Tritrics\AflevereApi\v1\Services\FileService;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Helper\GlobalHelper;

GlobalHelper::init([
  'version' => 'v1',
  'plugin-name' => 'tritrics/aflevere-api-v1',
  'namespace' => 'Tritrics\AflevereApi\v1'
]);

/**
 * Plugin registration
 */
kirby()::plugin(GlobalHelper::getPluginName(), [
  'options' => [
    'enabled' => [
      'info' => false,
      'page' => false,
      'pages' => false,
      'action' => false
    ],
    'slug' => 'public-api',
    'field-name-separator' => '_',
    'form-security' => [
      'secret' => null,
      'token-validity' => 10,
      'strip-tags' => true,
      'strip-backslashes' => true,
      'strip-urls' => true,
    ],
  ],
  'hooks' => [
    'page.create:before' => function ($page, array $input) {
      if (GlobalHelper::isProtectedSlug($input['slug'])) {
        throw new Exception('Slug not allowed');
      }
    },
    'page.changeSlug:before' => function ($page, string $slug, ?string $languageCode = null) {
      if (GlobalHelper::isProtectedSlug($slug)) {
        throw new Exception('Slug not allowed');
      }
    },
    'route:before' => function ($route, $path, $method) {
      $attributes = $route->attributes();
      if (
        $method === 'GET' &&
        isset($attributes['env']) &&
        $attributes['env'] === 'media' &&
        is_string($path) &&
        !is_file(kirby()->root('index') . '/' . $path)) {
          FileService::getImage($path, $route->arguments(), $route->pattern());
      }
      return;
    }
  ],
  'routes' => function ($kirby) {
    $slug = GlobalHelper::getApiSlug();
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
    if (GlobalHelper::isEnabledInfo()) {
      $routes[] = [
        'pattern' => $slug . '/info',
        'method' => 'GET',
        'action' => function () {
          $controller = new GetController();
          return $controller->info();
        }
      ];
    }

    // a language
    if (GlobalHelper::isEnabledLanguage()) {
      $routes[] = [
        'pattern' => $slug . '/language/(:any)',
        'method' => 'GET',
        'action' => function ($resource = '') use ($multilang) {
          $controller = new GetController();
          return $controller->language($resource);
        }
      ];
    }

    // a node
    if (GlobalHelper::isEnabledPage()) {
      $routes[] = [
        'pattern' => $slug . '/page/(:all?)',
        'method' => 'GET|POST|OPTIONS',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $path) = GlobalHelper::parsePath($resource, $multilang);
          $controller = new GetController();
          return $controller->page($lang, $path);
        }
      ];
    }

    // children of a node
    if (GlobalHelper::isEnabledPages()) {
      $routes[] = [
        'pattern' => $slug . '/pages/(:all?)',
        'method' => 'GET|POST|OPTIONS',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $path) = GlobalHelper::parsePath($resource, $multilang);
          $controller = new GetController();
          return $controller->pages($lang, $path);
        }
      ];
    }

    // action (post-data) handling
    if (GlobalHelper::isEnabledAction()) {

      // get a token, needed to submit an action
      $routes[] = [
        'pattern' => $slug . '/action/token/(:all?)',
        'method' => 'GET',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $action, $token) = GlobalHelper::parseAction($resource, $multilang);
          $controller = new ActionController();
          return $controller->token($action);
        }
      ];

      $routes[] = [
        'pattern' => $slug . '/action/submit/(:all?)',
        'method' => 'GET|POST|OPTIONS',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $action, $token) = GlobalHelper::parseAction($resource, $multilang);
          $controller = new ActionController();
          return $controller->submit($lang, $action, $token);
        }
      ];
    }
    return $routes;
  }
]);
