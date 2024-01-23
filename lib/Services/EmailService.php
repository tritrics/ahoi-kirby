<?php

namespace Tritrics\AflevereApi\v1\Services;

use Kirby\Exception\Exception;

/**
 * Sending emails as a helper API's form interface.
 */
class EmailService
{
  /**
   * Send emails of a given preset.
   *  
   * Presets are defined in config.php, use:
   *   [preset]-[something] or
   *   [preset]-[something].[lang] for multi-language
   * 
   * Required data:
   *   __from__ email
   *   __preset__ string
   * 
   * @param String $lang 
   * @param Array $data 
   * @return String|void 
   */
  public static function send ($lang, $data)
  {
    // from is optional
    if (isset($data['__from__']) && strlen($data['__from__'])) {
      if (!filter_var($data['__from__'], FILTER_VALIDATE_EMAIL)) {
        return 'Invalid sender address given for email action';
      } else {
        $from = $data['__from__'];
      }
    } else {
      $from = null; // in this case from must be given in mail preset
    }

    // preset is mandatory
    if (isset($data['__preset__']) && strlen($data['__preset__'])) {
      $presets = self::getPresets($data['__preset__'], $lang, $from);
      if (!$presets) {
        return 'No preset for email action defined'; 
      }
    } else {
      return 'Missing preset for email action';
    }

    try {
      $unsend = count($presets);
      foreach($presets as $preset) {
        $send = kirby()->email($preset['name'], [
          'to' => $preset['to'],
          'from' => $preset['from'],
          'data' => $data
        ])->isSent();
        if ($send) {
          $unsend--;
        }
      }
      if ($unsend > 0) {
        return 'Unknown error on sending ' . $unsend . ' email(s)';
      }
    } catch (Exception $error) {
      return $error->getMessage();
    }
  }

  /**
   * Detects all defined email presets for given $preset.
   * Checks present of to and from, where from can be given by form data
   * template-naming convention: name-something(.lang)?.[text|html].php
   * where -something is mandatory!
   * 
   * @param String $preset 
   * @param String $lang 
   * @param String $fromGiven 
   * @return Array 
   */
  private static function getPresets ($preset, $lang, $fromGiven)
  {
    $res = [];
    $basename = $preset . '-';
    $presets = kirby()->option('email.presets');
    foreach($presets as $name => $preset) {

      // basename defines, if preset belongs to this action
      if (substr($name, 0, strlen($basename)) !== $basename) {
        continue;
      }

      // these are presets with lang-part like 'shop-in.de', skip wrong lang
      $_parts = explode('.', $name);
      if (count($_parts) === 2 && $_parts[1] !== $lang) {
        continue;
      }

      // either to or from must be given in preset, otherwise user
      // sends mail from himself to himself
      if (!isset($preset['to']) && !isset($preset['from'])) {
        continue;
      }

      // to and from either from settings of given
      $to = isset($preset['to']) ? $preset['to'] : $fromGiven; // outbound
      $from = isset($preset['from']) ? $preset['from'] : $fromGiven; // inbound

      // skip configurations without to or from
      if (!$to || !$from) {
        continue;
      }

      // valid
      $res[] = [
        'name' => $name,
        'to' => $to,
        'from' => $from
      ];
    }
    return $res;
  }
}
