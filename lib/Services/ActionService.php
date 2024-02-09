<?php

namespace Tritrics\AflevereApi\v1\Services;

use Exception;
use Kirby\Cms\Page;
use Kirby\Toolkit\Str;
use Throwable;
use Tritrics\AflevereApi\v1\Data\Collection;
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
 * 120 Field value failed validation (the Kirby error message from error.validation is added)
 * 200 Error on sending %fail from %total mails.
 */
class ActionService
{
  private static $unhandledFieldTypes = [
    'blocks',
    'files',
    'gap',
    'headline',
    'info',
    'layout',
    'line',
    'list',
    'object',
    'pages',
    'structure',
    'users'
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
    $result = $body->add('result');

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
      $result->add('created', true);
      $result->add('id', $page->slug());
      $result->add('data', self::getInputData($action, $page));
    }

    // Error in field validation
    if (!$page->isValid()) {
      $errno->set(18);
      self::logError($action, 'Submitted post data failed validation.', 18); // @errno18
      KirbyHelper::deletePage($page);
      return $res->get();
    }

    // Executing additional actions
    $subActionFailed = false;
    if (isset($actions[$action]['email'])) {
      try {
        $resEmail = EmailAction::send($actions[$action]['email'], $page, $lang);
        $result->add('email', $resEmail);
        if ($resEmail['errno'] === 200) {
          self::logError($action, 'Error on sending %fail from %total mails.', 200, $resEmail);
        }
      } catch (Exception $E) {
        $subActionFailed = true;
        if ($E instanceof PayloadException) {
          $result->add('email', $E->getPayload());
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
    $uuid = Str::lower(Str::random(16, 'base32hex'));
    $hosts = RequestHelper::getHosts();
    $template = ConfigHelper::getConfig('actions.' . $action . '.template', '');

    // get Content
    // create a dummy page to get blueprint-fields
    $content = [];
    foreach (self::getFormFields($action) as $key) {
      $content[$key] = $data[$key] ?? null;
    }

    // add meta data to content
    $content['title']   = ConfigHelper::getConfig('actions.' . $action . '.title', 'Incoming %created (action %action)');
    $content['action']  = $action;
    $content['created'] = date('Y-m-d H:i:s', $now);
    $content['host']    = $hosts['referer']['host'];
    $content['ip']      = $hosts['referer']['ip'];
    $content['lang']    = $lang;
    $content['uuid']    = $uuid;

    foreach ($content as $key => $value) {
      if ($key === 'title' || $key === 'uuid') {
        continue;
      }
      if ((is_string($value) && !strstr($value, PHP_EOL)) || is_numeric($value)) {
        $content['title'] = str_replace('%' . $key, $value, $content['title']);
      }
    }

    // get config
    $params = [];
    $params['slug'] = $uuid;
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
    $params['num'] = 0; // is ignored, if status = draft
    $params['content'] = $content;
    return KirbyHelper::createPage(
      $params, ConfigHelper::getConfig('actions.' . $action . '.status', 'unlisted')
    );
  }

  /**
   * Get a list of field names from template, which have a proper type.
   * Optionally add build in fields.
   */
  private static function getFormFields (string $action, $add_build_in = false): array
  {
    $template = ConfigHelper::getConfig('actions.' . $action . '.template', '');
    $dummy = new Page(['slug' => 'dummy', 'template' => $template]);
    $res = $add_build_in ? ['title', 'action', 'created', 'host', 'ip', 'lang'] : [];
    foreach ($dummy->blueprint()->fields() as $key => $def) {
      if (!in_array($def['type'], self::$unhandledFieldTypes) && !in_array($key, $res)) {
        $res[] = $key;
      }
    }
    return $res;
  }

  /**
   * Helper to get the field-data out of Page model and add error codes.
   */
  private static function getInputData (string $action, Page $page): Collection
  {
    $res = new Collection();
    $errors = $page->errors();
    foreach (self::getFormFields($action) as $key) {
      $field = $res->add($key);
      $field->add('value', $page->$key()->value());
      if (isset($errors[$key])) {
        $field->add('errno', 120);
        if (isset($errors[$key]['message']) && count($errors[$key]['message']) > 0) {
          $field->add('errmsg', array_key_first($errors[$key]['message']));
        } else {
          $field->add('errmsg', 'unknown');
        }
      } else {
        $field->add('errno', 0);
      }
    }
    return $res;
  }

  /**
   * Log errors to PHP error log.
   */
  private static function logError(
    string $action,
    ?string $message = '',
    mixed $errno = 1,
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
