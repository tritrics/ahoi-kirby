<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Exception\DuplicateException;
use Kirby\Exception\LogicException;
use Tritrics\AflevereApi\v1\Services\ApiService;
use Tritrics\AflevereApi\v1\Services\LanguagesService;
use Tritrics\AflevereApi\v1\Models\LanguagesModel;

/**
 * Service for API's info interface.
 */
class InfoService
{
  /**
   * Main method to respond to "info" action.
   * 
   * @return Response 
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get()
  {
    $expose = kirby()->option('debug', false);
    $isMultilang = LanguagesService::isMultilang();

    $res = ApiService::initResponse();
    $body = $res->add('body');

    // Type
    $body->add('type', 'info');

    // Meta
    $meta = $body->add('meta');
    $meta->add('multilang', $isMultilang);
    if ($expose) {
      $meta->add('api', ApiService::$version);
      $meta->add('plugin', ApiService::getPluginVersion());
      $meta->add('kirby', kirby()->version());
      $meta->add('php', phpversion());
      $meta->add('slug', ApiService::getconfig('slug', ''));
      $meta->add('field-name-separator',  ApiService::getconfig('field-name-separator', ''));
    }

    // Interface
    if ($expose) {
      $interface = $body->add('interface');
      $Request = kirby()->request();
      $url = substr($Request->url()->toString(), 0, -5); // the easy way
      if (ApiService::isEnabledInfo()) {
        $interface->add('info', $url . '/info',);
      }
      if (ApiService::isEnabledLanguage()) {
        $interface->add('language', $url . '/language',);
      }
      if (ApiService::isEnabledPage()) {
        $interface->add('page', $url . '/page');
      }
      if (ApiService::isEnabledPages()) {
        $interface->add('pages', $url . '/pages');
      }
      if (ApiService::isEnabledForm()) {
        $interface->add('form', $url . '/form');
      }
    }

    // add languages
    if ($isMultilang) {
      $value = $body->add('value');
      $languages = new LanguagesModel(LanguagesService::getLanguages());
      $value->add('languages', $languages);
    }
    return $res->get();
  }
}