<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Exception\DuplicateException;
use Kirby\Exception\LogicException;
use Tritrics\AflevereApi\v1\Helper\GlobalHelper;
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
   * @return Array 
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get()
  {
    $expose = kirby()->option('debug', false);
    $isMultilang = LanguagesService::isMultilang();

    $res = GlobalHelper::initResponse();
    $body = $res->add('body');

    // Type
    $body->add('type', 'info');

    // Meta
    $meta = $body->add('meta');
    $meta->add('multilang', $isMultilang);
    if ($expose) {
      $meta->add('api', GlobalHelper::getVersion());
      $meta->add('plugin', GlobalHelper::getPluginVersion());
      $meta->add('kirby', kirby()->version());
      $meta->add('php', phpversion());
      $meta->add('slug', GlobalHelper::getconfig('slug', ''));
      $meta->add('field-name-separator',  GlobalHelper::getconfig('field-name-separator', ''));
    }

    // Interface
    if ($expose) {
      $interface = $body->add('interface');
      $Request = kirby()->request();
      $url = substr($Request->url()->toString(), 0, -5); // the easy way
      if (GlobalHelper::isEnabledInfo()) {
        $interface->add('info', $url . '/info',);
      }
      if (GlobalHelper::isEnabledLanguage()) {
        $interface->add('language', $url . '/language',);
      }
      if (GlobalHelper::isEnabledPage()) {
        $interface->add('page', $url . '/page');
      }
      if (GlobalHelper::isEnabledPages()) {
        $interface->add('pages', $url . '/pages');
      }
      if (GlobalHelper::isEnabledAction()) {
        $interface->add('action', $url . '/action');
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