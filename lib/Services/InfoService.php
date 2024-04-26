<?php

namespace Tritrics\Tric\v1\Services;

use Kirby\Exception\DuplicateException;
use Kirby\Exception\LogicException;
use Tritrics\Tric\v1\Helper\ConfigHelper;
use Tritrics\Tric\v1\Helper\ResponseHelper;
use Tritrics\Tric\v1\Helper\KirbyHelper;
use Tritrics\Tric\v1\Models\LanguageModel;

/**
 * Service for API's info interface.
 */
class InfoService
{
  /**
   * Main method to respond to "info" action.
   *
   * @throws DuplicateException 
   * @throws LogicException 
   */
  public static function get(): array
  {
    $expose = kirby()->option('debug', false);
    $isMultilang = ConfigHelper::isMultilang();

    $res = ResponseHelper::getHeader();
    $body = $res->add('body');

    // Type
    $body->add('type', 'info');

    // Meta
    $meta = $body->add('meta');
    $meta->add('multilang', $isMultilang);
    if ($expose) {
      $meta->add('api', ConfigHelper::getVersion());
      $meta->add('plugin', ConfigHelper::getPluginVersion());
      $meta->add('kirby', kirby()->version());
      $meta->add('php', phpversion());
      $meta->add('slug', ConfigHelper::getconfig('slug', ''));
      $meta->add('field-name-separator',  ConfigHelper::getconfig('field-name-separator', ''));
    }

    // Interface
    if ($expose) {
      $interface = $body->add('interface');
      $Request = kirby()->request();
      $url = substr($Request->url()->toString(), 0, -5); // the easy way
      if (ConfigHelper::isEnabledInfo()) {
        $interface->add('info', $url . '/info',);
      }
      if (ConfigHelper::isEnabledLanguage()) {
        $interface->add('language', $url . '/language',);
      }
      if (ConfigHelper::isEnabledPage()) {
        $interface->add('page', $url . '/page');
      }
      if (ConfigHelper::isEnabledPages()) {
        $interface->add('pages', $url . '/pages');
      }
      if (ConfigHelper::isEnabledAction()) {
        $interface->add('action', $url . '/action');
      }
    }

    // add languages
    if ($isMultilang) {
      $languages = $body->add('languages');
      foreach(KirbyHelper::getLanguages() as $model) {
        $languages->push(new LanguageModel($model));
      }
    }
    return $res->get();
  }
}