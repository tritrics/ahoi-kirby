<?php

use Tritrics\Ahoi\v1\Controllers\ActionController;
use Tritrics\Ahoi\v1\Controllers\CollectionController;
use Tritrics\Ahoi\v1\Controllers\InfoController;
use Tritrics\Ahoi\v1\Controllers\LanguageController;
use Tritrics\Ahoi\v1\Controllers\NodeController;
use Tritrics\Ahoi\v1\Controllers\OptionsController;
use Tritrics\Ahoi\v1\Services\ImageService;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\RequestHelper;

ConfigHelper::init([
  'version' => 'v1',
  'plugin-name' => 'tritrics/ahoi-v1',
  'namespace' => 'Tritrics\Ahoi\v1'
]);

/**
 * Plugin registration
 */
kirby()::plugin(ConfigHelper::getPluginName(), [
  'options' => [
    'enabled' => false,
    'slug' => 'public-api',
    'field_name_separator' => '_',
    'form_security' => [
      'secret' => null,
      'token_validity' => 10,
      'return_post_values' => false,
      'strip_tags' => true,
      'strip_backslashes' => true,
    ],
  ],
  'hooks' => [
    'page.create:before' => function ($page) {
      if (ConfigHelper::isEnabled()) {
        ConfigHelper::checkSlug($page->uri());
      }
    },
    'page.changeSlug:before' => function ($page, $slug) {
      if (ConfigHelper::isEnabled()) {
        ConfigHelper::checkSlug($slug);
      }
    },
    'page.duplicate:before' => function ($page, $input) {
      if (ConfigHelper::isEnabled()) {
        ConfigHelper::checkSlug($input);
      }
    },
    'route:before' => function ($route, $path, $method) {
      $attributes = $route->attributes();
      if (
        ConfigHelper::isEnabled() &&
        $method === 'GET' &&
        isset($attributes['env']) &&
        $attributes['env'] === 'media' &&
        is_string($path) &&
        !is_file(kirby()->root('index') . '/' . $path)
      ) {
        ImageService::get($path, $route->arguments(), $route->pattern());
      }
      return;
    }
  ],
  'routes' => function ($kirby) {
    $slug = ConfigHelper::getApiSlug();
    if (!ConfigHelper::isEnabled() || !$slug) {
      return [];
    }
    $multilang = ConfigHelper::isMultilang();
    $routes = array();

    // default kirby route must be overwritten to prevent kirby
    // from redirecting to default language. This is done by
    // the frontend.
    if ($multilang) {
      $routes[] = [
        'pattern' => '',
        'method'  => 'ALL',
        'env'     => 'site',
        'action'  => function () use ($kirby) {
          return $kirby->defaultLanguage()->router()->call();
        }
      ];
    }

    // OPTIONS > pre-flight
    $routes[] = [
      'pattern' => $slug . '/(:all?)',
      'method' => 'OPTIONS',
      'action' => function () {
        $controller = new OptionsController();
        return $controller->options();
      }
    ];

    // expose
    $routes[] = [
      'pattern' => $slug . '/info',
      'method' => 'GET',
      'action' => function () {
        $controller = new InfoController();
        return $controller->info();
      }
    ];

    // a language
    if ($multilang) {
      $routes[] = [
        'pattern' => $slug . '/language/(:any)',
        'method' => 'GET|POST',
        'action' => function ($resource = '') use ($multilang) {
          $controller = new LanguageController();
          return $controller->language($resource);
        }
      ];
    }

    // a page
    $routes[] = [
      'pattern' => $slug . '/page/(:all?)',
      'method' => 'GET|POST',
      'action' => function ($resource = '') use ($multilang) {
        list($lang, $path) = RequestHelper::parsePath($resource, $multilang);
        $controller = new NodeController();
        return $controller->page($lang, $path);
      }
    ];

    // a file
    $routes[] = [
      'pattern' => $slug . '/file/(:all?)',
      'method' => 'GET|POST',
      'action' => function ($resource = '') use ($multilang) {
        list($lang, $path) = RequestHelper::parsePath($resource, $multilang);
        $controller = new NodeController();
        return $controller->file($lang, $path);
      }
    ];

    // children of a page
    $routes[] = [
      'pattern' => $slug . '/pages/(:all?)',
      'method' => 'GET|POST',
      'action' => function ($resource = '') use ($multilang) {
        list($lang, $path) = RequestHelper::parsePath($resource, $multilang);
        $controller = new CollectionController();
        return $controller->pages($lang, $path);
      }
    ];

    // children of a page
    $routes[] = [
      'pattern' => $slug . '/files/(:all?)',
      'method' => 'GET|POST',
      'action' => function ($resource = '') use ($multilang) {
        list($lang, $path) = RequestHelper::parsePath($resource, $multilang);
        $controller = new CollectionController();
        return $controller->files($lang, $path);
      }
    ];

    // create (update, delete) pages, send emails etc.
    if (ConfigHelper::hasActions()) {

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
