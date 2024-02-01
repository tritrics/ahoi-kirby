<?php

namespace Tritrics\AflevereApi\v1\Services;

use Tritrics\AflevereApi\v1\Data\SimpleCollection;
use Tritrics\AflevereApi\v1\Helper\TokenHelper;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\ResponseHelper;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;
use Tritrics\AflevereApi\v1\Actions\EmailAction;
use Tritrics\AflevereApi\v1\Post\PostData;
use Tritrics\AflevereApi\v1\Post\BaseValue;

/**
 * Handling actions (post-data)
 */
class ActionService
{
  /**
   * Error messages, only stored to error-log, not published.
   * 
   * @var array
   */
  private static $errors = [

    // Fatal errors
    // 1 - 19 Global
    // 20 - 29 EmailAction
    // 30 - 99 unused
    1 => 'An unknown error occured.',
    10 => 'Action configuration is missing or incomplete in config.php.',
    11 => 'Security token is missing in config or doesn\'t match the requirements.',
    //15 => 'Action was declined due to security concerns.',
    17 => 'Post-input configuration is missing or incomplete in config.php.',
    19 => 'Submitted data was not saved because all sub-actions failed.',
    20 => 'All mail configurations in config.php are invalid, nothing to send.',
    21 => 'No valid inbound mail action configured in config.php.',
    22 => 'Sending failed for all inbound mails.',

    // Non-fatal errors
    // 100 - 199 Global
    // 200 - 299 EmailAction
    // 300 - 999 unused
    100 => 'Error in one or more sub-actions.',
    110 => 'Submitted post data failed validation.',
    120 => 'Field is of wrong data type.',
    121 => 'Field is required.',
    122 => 'Value is not matching the required min/max.',
    200 => 'Error on sending %fail from %total mails.',
  ];

  /**
   * Getting a token.
   */
  public static function token (string $action): array
  {
    $res = ResponseHelper::getHeader();
    $body = $res->add('body');
    $body->add('action', $action);
    $body->add('token', TokenHelper::get($action));
    return $res->get();
  }

  /**
   * Main function to submit (execute) a given action.
   * Token and action are already checked by controller.
   */
  public static function submit(string $lang, string $action, array $data): array
  {
    // init response
    $res = ResponseHelper::getHeader();
    $body = $res->add('body');
    $body->add('action', $action);
    $errno = $body->add('errno', 0);
    $protocol = $body->add('result');

    // read post data and validate
    $post = new PostData($action, $data);
    $protocol->add('data', $post->getResult(
      ConfigHelper::getConfig('form-security.return-post-values', false)
    ));
    if ($post->hasError()) {
      $errno->set($post->getError());
      self::logError($action, $post->getError());
      return $res->get();
    }

    // read actions config
    $actions = ConfigHelper::getConfig('actions');

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
    $meta = new SimpleCollection(self::getMeta($lang));
    $data = new SimpleCollection($post->get());

    // ... other actions, evtl. set $isSaved = true

    if (isset($actions[$action]['email'])) {
      $resEmail = EmailAction::send($actions[$action]['email'], $meta, $data, $lang, !$isSaved);
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

  /**
   * Compute some meta values for use in actions.
   */
  private static function getMeta(?string $lang): array
  {
    $hosts = RequestHelper::getHosts($lang);
    return [
      'date' => new BaseValue(date('Y-m-d')),
      'time' => new BaseValue(date('H:i:s')),
      'host' => new BaseValue($hosts['referer']['host']),
      'ip' => new BaseValue($hosts['referer']['ip']),
      'lang' => new BaseValue($lang),
    ];
  }

  /**
   * Log errors to PHP error log.
   */
  private static function logError(string $action, int $errno, ?array $parse = []): void
  {
    $message = isset(self::$errors[$errno]) ? self::$errors[$errno] : self::$errors[1];
    if (is_array($parse)) {
      foreach ($parse as $key => $value) {
        $message = str_replace('%' . $key, $value, $message);
      }
    }
    error_log(ConfigHelper::getPluginName() . ': Error ' . $errno . ' on excecuting /action/' . $action . ' (' . $message . ')');
  }
}
