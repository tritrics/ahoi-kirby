<?php

namespace Tritrics\AflevereApi\v1\Services;

use Exception;
use Kirby\Cms\Page;
use Throwable;
use Tritrics\AflevereApi\v1\Exceptions\PayloadException;
use Tritrics\AflevereApi\v1\Helper\TokenHelper;
use Tritrics\AflevereApi\v1\Helper\ConfigHelper;
use Tritrics\AflevereApi\v1\Helper\ResponseHelper;
use Tritrics\AflevereApi\v1\Actions\EmailAction;
use Tritrics\AflevereApi\v1\Helper\RequestHelper;
use Tritrics\AflevereApi\v1\Helper\KirbyHelper;

/**
 * Handling actions (post-data)
 * 
 * Error messages, only stored to error-log, not published.
 * 
 * Fatal errors:
 * 1 - 19 Global
 * 20 - 29 EmailAction
 * 30 - 99 unused
 * 
 *  1 An unknown error occured.
 * 10 Action configuration is missing or incomplete in config.php.
 * 11 Security token is missing in config or doesn\'t match the requirements.
 * 15 Action was declined due to security concerns. // not used, reserved
 * 17 Post data configuration is missing or incomplete in config.php.
 * 18 Submitted post data failed validation.
 * 21 No valid inbound mail action configured in config.php.
 * 22 Sending failed for all inbound mails.
 * 
 * Non-fatal errors
 * 100 - 199 Global
 * 200 - 299 EmailAction
 * 300 - 999 unused
 * 
 * 100 Error in one or more sub-actions.
 * 120 Field value failed validation.
 * 200 Error on sending %fail from %total mails.
 */
class ActionService
{
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

    // read actions config
    $actions = ConfigHelper::getConfig('actions');

    // actions.[action] is not existing or is not an array
    if (
      !is_array($actions) ||
      count($actions) === 0 ||
      !isset($actions[$action]) ||
      !is_array($actions[$action])
    ) {
      $errno->set(10);
      self::logError($action, 'Action configuration is missing or incomplete in config.php.', 10); // @errno10
      return $res->get();
    }

    // read post data and validate
    try {
      $page = self::createPage($lang, $action, $data);
    } catch (Exception $E) {
      $errno->set($E->getCode());
      self::logError($action, $E->getMessage(), $E->getCode());
      return $res->get();
    }

    // write post data to result
    if (ConfigHelper::getConfig('form-security.return-post-values', false)) {
      $input = [];
      foreach( $page->content()->data() as $key => $value) {
        if ($key === 'uuid') continue;
        $input[$key] = [ 'value' => $value, 'errno' => 0 ];
      }
      foreach ($page->errors() as $key => $error) {
        $input[$key]['errno'] = 120;
      };
      $protocol->add('input', $input);
    }

    // Error in field validation
    if (!$page->isValid()) {
      $errno->set(18);
      self::logError($action, 'Submitted post data failed validation.', 18); // @errno18
      KirbyHelper::deletePage($page);
      return $res->get();
    }

    return $res->get();

    // Executing additional actions
    $subActionFailed = false;
    if (isset($actions[$action]['email'])) {
      try {
        $resEmail = EmailAction::send($actions[$action]['email'], $page, $lang);
        $protocol->add('email', $resEmail);
        if ($resEmail['errno'] === 200) {
          self::logError($action, 'Error on sending %fail from %total mails.', 200, $resEmail);
        }
      } catch (Exception $E) {
        $subActionFailed = true;
        if ($E instanceof PayloadException) {
          $protocol->add('email', $E->getPayload());
        }
        self::logError($action, $E->getMessage(), $E->getCode());
      }
    }

    // ... other actions

    // overall errors in actions
    if ($subActionFailed) {
      $errno->set(100);
      self::logError($action, 'Error in one or more actions.', 100); // @errno100
    }
    return $res->get();
  }

  /**
   * Creates as page from post data
   *
   * @throws Exception 
   * @throws Throwable 
   */
  private static function createPage(string $lang, string $action, array $data): Page
  {
    $now = time();
    $hosts = RequestHelper::getHosts();
    $template = ConfigHelper::getConfig('actions.' . $action . '.template', '');

    // get Content
    // create a dummy page to get blueprint-fields
    $dummy = new Page(['slug' => 'dummy', 'template' => $template]);
    $content = [];
    foreach ($dummy->blueprint()->fields() as $key => $def) {
      $content[$key] = $data[$key] ?? null;
    }

    // add meta data to content, where content overwrites meta data in case of name conflicts
    $add = [];
    $add['title'] = str_replace(
      ['%action', '%date', '%time', '%ip', '$host', '%lang'],
      [$action, date('Y-m-d', $now), date('H:i:s', $now), $hosts['referer']['ip'], $hosts['referer']['host'], $lang],
      ConfigHelper::getConfig('actions.' . $action . '.title', 'Submit %action $date $time')
    );
    $add['created'] = date('Y-m-d H:i:s');
    $add['host']    = $hosts['referer']['host'];
    $add['ip']      = $hosts['referer']['ip'];
    $add['lang']    = $lang;
    $content = array_merge($add, $content);

    // get config
    $params = [];
    $params['slug'] = TokenHelper::generateId($now . '-' . rand(100, 999));
    $params['template'] = $template;
    if (strlen($params['template']) === 0) {
      throw new Exception('Action configuration is missing or incomplete in config.php.', 17); // @errno17
    }
    $parentSlug = ConfigHelper::getConfig('actions.' . $action . '.parent', '');
    if (strlen($parentSlug) > 0) {
      $params['parent'] = KirbyHelper::findPage($parentSlug);
      if (!$params['parent'] instanceof Page) {
        throw new Exception('Action configuration is missing or incomplete in config.php.', 17); // @errno17
      }
    }
    $params['isDraft'] = ConfigHelper::getConfig('actions.' . $action . '.isDraft', false);
    $params['content'] = $content;

    return KirbyHelper::createPage($params);
  }

  /**
   * Log errors to PHP error log.
   */
  private static function logError(
    string $action,
    ?string $message = '',
    ?int $errno = 1,
    ?array $parse = []
  ): void {
    if (!strlen($message)) {
      $message = 'An unknown error occured.';
    }
    if (is_array($parse)) {
      foreach ($parse as $key => $value) {
        $message = str_replace('%' . $key, $value, $message);
      }
    }
    error_log(ConfigHelper::getPluginName() . ': Error ' . $errno . ' on excecuting /action/' . $action . ' (' . $message . ')');
  }
}
