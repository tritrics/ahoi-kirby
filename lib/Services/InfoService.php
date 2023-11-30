<?php

namespace Tritrics\AflevereApi\v1\Services;

use Tritrics\AflevereApi\v1\Services\ApiService;
use Tritrics\AflevereApi\v1\Services\LanguageService;

class InfoService
{
  public static function get()
  {
    $expose = kirby()->option('debug', false);
    $isMultilang = LanguageService::isMultilang();

    $res = ApiService::initResponse();
    $body = $res->add('body');
    $body->add('type', 'info');

    // meta
    $meta = $body->add('meta');
    $meta->add('multilang', $isMultilang);

    // add languages
    $value = $body->add('value');
    if ($isMultilang) {
      $meta->add('languages', LanguageService::count());
      $languages = $value->add('languages');
      $languages->add('type', 'languages');
      $languages->add('value', LanguageService::get());
    }
    if ($expose) {
      $meta->add('api', ApiService::$version);
      $meta->add('plugin', ApiService::getPluginVersion());
      $meta->add('kirby', kirby()->version());
      $meta->add('php', phpversion());
      $meta->add('slug', ApiService::getconfig('slug', ''));
      $meta->add('field-name-separator',  ApiService::getconfig('field-name-separator', ''));
      $Request = kirby()->request();
      $url = substr($Request->url()->toString(), 0, -5); // the easy way
      if (ApiService::isEnabledInfo()) {
        $meta->add('info', $url . '/info',);
      }
      if (ApiService::isEnabledNode()) {
        $meta->add('node', $url . '/node');
      }
      if (ApiService::isEnabledNodes()) {
        $meta->add('nodes', $url . '/nodes');
      }
      if (ApiService::isEnabledForm()) {
        $meta->add('form', $url . '/form');
      }
    }
    return $res->get();
  }
}