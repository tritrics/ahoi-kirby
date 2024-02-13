<?php

use Kirby\Exception\Exception;
use Tritrics\AflevereApi\v1\Controllers\GetController;
use Tritrics\AflevereApi\v1\Controllers\ActionController;
use Tritrics\AflevereApi\v1\Services\FileService;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;

ConfigHelper::init([
  'version' => 'v1',
  'plugin-name' => 'tritrics/aflevere-api-v1',
  'namespace' => 'Tritrics\AflevereApi\v1'
]);

/**
 * Plugin registration
 */
kirby()::plugin(ConfigHelper::getPluginName(), [
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
      'return-post-values' => false,
      'strip-tags' => true,
      'strip-backslashes' => true,
    ],
  ],
  'hooks' => [
    'page.create:before' => function ($page, array $input) {
      if (ConfigHelper::isProtectedSlug($input['slug'])) {
        throw new Exception('Slug not allowed');
      }
    },
    'page.changeSlug:before' => function ($page, string $slug, ?string $languageCode = null) {
      if (ConfigHelper::isProtectedSlug($slug)) {
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
    $slug = ConfigHelper::getApiSlug();
    if (!$slug) {
      return [];
    }
    $multilang = ConfigHelper::isMultilang();
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
    if (ConfigHelper::isEnabledInfo()) {
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
    if (ConfigHelper::isEnabledLanguage()) {
      $routes[] = [
        'pattern' => $slug . '/language/(:any)',
        'method' => 'GET',
        'action' => function ($resource = '') use ($multilang) {
          $controller = new GetController();
          return $controller->language($resource);
        }
      ];
    }

    // a page
    if (ConfigHelper::isEnabledPage()) {
      $routes[] = [
        'pattern' => $slug . '/page/(:all?)',
        'method' => 'GET',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $path) = RequestHelper::parsePath($resource, $multilang);
          $controller = new GetController();
          return $controller->page($lang, $path);
        }
      ];
    }

    // children of a page
    if (ConfigHelper::isEnabledPages()) {
      $routes[] = [
        'pattern' => $slug . '/pages/(:all?)',
        'method' => 'GET',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $path) = RequestHelper::parsePath($resource, $multilang);
          $controller = new GetController();
          return $controller->pages($lang, $path);
        }
      ];
    }

    // create (update, delete) pages, send emails etc.
    if (ConfigHelper::isEnabledAction()) {

      // GET > return a token, needed to submit an action
      $routes[] = [
        'pattern' => $slug . '/token/(:all?)',
        'method' => 'GET',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $action, $token) = RequestHelper::parseAction($resource, $multilang);
          $controller = new ActionController();
          return $controller->token($action);
        }
      ];

      // GET > get an entry
      $routes[] = [
        'pattern' => $slug . '/action/(:all?)',
        'method' => 'GET',
        'action' => function () {
          $controller = new ActionController();
          return $controller->get();
        }
      ];

      // OPTIONS > pre-flight
      $routes[] = [
        'pattern' => $slug . '/action/(:all?)',
        'method' => 'OPTIONS',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $action, $token) = RequestHelper::parseAction($resource, $multilang);
          $controller = new ActionController();
          return $controller->options($lang, $action, $token);
        }
      ];

      // POST > create
      $routes[] = [
        'pattern' => $slug . '/action/(:all?)',
        'method' => 'POST',
        'action' => function ($resource = '') use ($multilang) {
          list($lang, $action, $token) = RequestHelper::parseAction($resource, $multilang);
          $controller = new ActionController();
          return $controller->create($lang, $action, $token);
        }
      ];

      // PUT > update (PATCH > partial update)
      $routes[] = [
        'pattern' => $slug . '/action/(:all?)',
        'method' => 'PUT',
        'action' => function () {
          $controller = new ActionController();
          return $controller->update();
        }
      ];

      // DELETE > delete
      $routes[] = [
          'pattern' => $slug . '/action/(:all?)',
          'method' => 'DELETE',
          'action' => function () {
            $controller = new ActionController();
            return $controller->delete();
          }
        ];
    }
    return $routes;
  }
]);
