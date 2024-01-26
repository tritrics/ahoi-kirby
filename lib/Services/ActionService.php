<?php

namespace Tritrics\AflevereApi\v1\Services;

use Exception;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Actions\EmailAction;
use Tritrics\AflevereApi\v1\Services\ApiService;

/**
 * Handling actions (post-data)
 */
class ActionService
{
  private static $errors = [

    // Fatal errors
    // 1 - 19 Global
    // 20 - 29 EmailAction
    // 30 - 99 unused
    1 => 'An unknown error occured.',
    10 => 'Configuration is missing or incomplete in config.php.',
    15 => 'Action was declined due to security concerns.', // not used so far
    19 => 'Submitted data was not saved because all sub-actions failed.',
    20 => 'All mail configurations in config.php are invalid, nothing to send.',
    21 => 'No valid inbound mail action configured in config.php.',
    22 => 'Sending failed for all inbound mails.',

    // Non-fatal errors
    // 100 - 199 Global
    // 200 - 299 EmailAction
    // 300 - 999 unused
    100 => 'Error in one or more sub-actions.',
    200 => 'Error on sending %fail from %total mails.',
  ];

  /**
   * Main function to execute a given action.
   * 
   * @param String $lang 
   * @param String $action 
   * @param Array $data 
   * @return Response
   */
  public static function do($lang, $action, $data)
  {
    // read config data
    $actions = ApiService::getConfig('actions');

    $res = ApiService::initResponse();
    $body = $res->add('body');
    $body->add('action', $action);
    $errno = $body->add('errno', 0);
    $body->add('data', $data);
    $protocol = $body->add('result');

    // @errno10: Configuration is missing or incomplete in config.php. 
    // actions.[action] is not existing or is not an array
    if (
      !is_array($actions) ||
      count($actions) === 0 ||
      !isset($actions[$action]) ||
      !is_array($actions[$action]) ||
      count($actions[$action]) === 0
    ) {
      $errno->set(10);
      self::logError($action, 10);
      return $res->get();
    }

    // At least one action which saves the data must be successfull.
    $isSaved = false;
    $subActionFailed = false;

    // ... other actions, evtl. set $isSaved = true

    if (isset($actions[$action]['email'])) {
      $resEmail = EmailAction::send($actions[$action]['email'], $data, $lang, !$isSaved);
      if ($resEmail['errno'] > 0) {
        $subActionFailed = true;
        self::logError($action, $resEmail['errno'], $resEmail);
      }
      if ($resEmail['errno'] === 0 || $resEmail['errno'] >= 100) {
        $isSaved = true;
      }
      $protocol->add('email', $resEmail);
    }

    // @errno19: Submitted data was not saved because all sub-actions failed.
    if (!$isSaved) {
      $errno->set(19);
      self::logError($action, 19);
    }
    
    // @errno100: Error in one or more sub-actions.
    else if ($subActionFailed) {
      $errno->set(100);
      self::logError($action, 100);
    }
    
    return $res->get();
  }

  private static function logError($action, $errno, $parse = [])
  {
    $message = isset(self::$errors[$errno]) ? self::$errors[$errno] : self::$errors[1];
    if (is_array($parse)) {
      foreach ($parse as $key => $value) {
        $message = str_replace('%' . $key, $value, $message);
      }
    }
    error_log(ApiService::$pluginName . ': Error ' . $errno . ' on excecuting /action/' . $action . ' (' . $message . ')');
  }
}
