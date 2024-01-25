<?php

namespace Tritrics\AflevereApi\v1\Services;

use Exception;
use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Services\ApiService;

/**
 * Handling actions (post-data)
 */
class ActionService
{
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
    try {

      // read config data
      $actions = ApiService::getConfig('actions');

      // abort if actions.[action] is not existing or is not an array
      if (
        !is_array($actions) ||
        count($actions) === 0 ||
        !isset($actions[$action]) ||
        !is_array($actions[$action]) ||
        count($actions[$action]) === 0
      ) {
        throw new Exception('Configuration is missing or incomplete in config.php');
      }

      // loop actions
      $protocol = [];
      foreach($actions[$action] as $type => $presets) {
        switch($type) {
          case 'email':
            $protocol['email'] = self::email($presets, $lang, $data);
            break;
        }
      }

      $res = ApiService::initResponse();
      $body = new Collection();
      $body->add('action', $action);
      $body->add('lang', $lang);
      $body->add('data', $data);
      $body->add('actions', $protocol);
      $res->add('body', $body);
      return $res->get();

    } catch (Exception $E) {
      error_log(ApiService::$pluginName . ': Error 501 on excecuting /action/' . $action . ' (' . $E->getMessage() . ')');
      return ApiService::notimplemented();
    }
  }

  /**
   * Sending emails
   * 
   * @param Array $emails configuration from config actions.emails
   * @param String $lang 2-char lang code
   * @param Array $data the form data to be parsed into the email template
   * @return Array result protocol 
   * @throws Exception 
   */
  private static function email($presets, $lang, $data)
  {
    $hosts = GlobalService::getHosts($lang);

    // adding meta to $data
    $meta = [];
    $meta['__date__'] = date('Y-m-d');
    $meta['__time__'] = date('H:i:s');
    $meta['__host__'] = $hosts['referer']['host'];
    $meta['__ip__']   = $hosts['referer']['ip'];
    if (is_string($lang)) {
      $meta['__lang__'] = $lang;
    }
    $data = array_merge($meta, $data);

    // computing the mails from $presets
    $emails = self::getEmails($presets, $lang, $data, $hosts);
    if ($emails === null) {
      throw new Exception('No valid ingoing mail action configured in config.php'); 
    }

    // sending
    $res = [ 'total' => count($emails),  'success' => 0 ];
    $success = true;
    foreach($emails as $email) {
      $success = kirby()->email($email)->isSent();
      if ($success) {
        $res['success']++;
      }
      unset($email['body']);
      error_log(print_r($email, true));
    }
    if ($res['success'] === 0) {
      throw new Exception('Error on sending ingoing mails');
    }
    return $res;
  }

  /**
   * Helper to get a list with objects of email configuration, same structure
   * like it would be configures in config.php email.presets.
   * https://getkirby.com/docs/guide/emails
   * 
   * @param Array $emails configuration from config actions.emails
   * @param String $lang 2-char lang code
   * @param Array $data the form data to be parsed into the email template
   * @param Array $hosts
   * @return Array the checked and completed mails to send, sorted by in and out 
   */
  private static function getEmails($presets, $lang, $data, $hosts)
  {
    $res = [];
    $hasIngoing = false;
    foreach ($presets as $preset) {
      $email = [];

      // from, one, required
      if (isset($preset['from'])) {
        $email['from'] = self::getAddresses($preset['from'], $data);
        if ($email['from'] === null || is_array($email['from'])) {
          continue;
        }
      } else {
        continue;
      }

      // from name, optional
      if (
        isset($preset['fromName']) &&
        is_string($preset['fromName']) &&
        strlen($preset['fromName']) > 0
      ) {
        $email['fromName'] = 
          isset($data[$preset['fromName']])
            ? $data[$preset['fromName']]
            : $preset['fromName'];
      }

      // to, one or multiple, required
      if (isset($preset['to'])) {
        $email['to'] = self::getAddresses($preset['to'], $data);
        if ($email['to'] === null) {
          continue;
        }
        $hasIngoing = self::isIngoing($preset['to'], $hasIngoing);
      } else {
        continue;
      }

      // reply to, one, optional
      if (isset($preset['replyTo'])) {
        $email['replyTo'] = self::getAddresses($preset['replyTo'], $data);
        if ($email['replyTo'] === null || is_array($email['replyTo'])) {
          unset($email['replyTo']);
        }
      }

      // replay to name, optional
      if (
        isset($email['replyTo']) &&
        isset($preset['replyToName']) &&
        is_string($preset['replyToName']) &&
        strlen($preset['replyToName']) > 0
      ) {
        $email['replyToName'] =
          isset($data[$preset['replyToName']])
            ? $data[$preset['replyToName']]
            : $preset['replyToName'];
      }

      // cc, optional
      if (isset($preset['cc'])) {
        $email['cc'] = self::getAddresses($preset['cc'], $data);
        if ($email['cc'] === null) {
          unset($email['cc']);
        }
        $hasIngoing = self::isIngoing($preset['cc'], $hasIngoing);
      }

      // bcc optional
      if (isset($preset['bcc'])) {
        $email['bcc'] = self::getAddresses($preset['bcc'], $data);
        if ($email['bcc'] === null) {
          unset($email['bcc']);
        }
        $hasIngoing = self::isIngoing($preset['bcc'], $hasIngoing);
      }
      
      // subject, lang-specific, required
      if (
        is_string($lang) &&
        isset($preset['subject-' . $lang]) &&
        is_string($preset['subject-' . $lang]) &&
        strlen($preset['subject-' . $lang]) > 0
      ) {
        $email['subject'] = $preset['subject-' . $lang];
      } else if (
        isset($preset['subject']) &&
        is_string($preset['subject']) &&
        strlen($preset['subject']) > 0
      ) {
        $email['subject'] = $preset['subject'];
      } else {
        $email['subject'] = 'Message from ' . $hosts['self']['host'];
      }

      // body, lang-specific, required
      $email['body'] = null;
      if (
        is_string($lang) &&
        isset($preset['template-' . $lang]) &&
        is_string($preset['template-' . $lang]) &&
        strlen($preset['template-' . $lang]) > 0
      ) {
        $email['body'] = self::parseTemplate($preset['template-' . $lang], $data);
      }
      if (
        $email['body'] === null &&
        isset($preset['template']) &&
        is_string($preset['template']) &&
        strlen($preset['template']) > 0
      ) {
        $email['body'] = self::parseTemplate($preset['template'], $data);
      }
      if ($email['body'] === null) {
        $email['body'] = self::buildInTemplate($data);
      }

      // attachments, optional
      if (isset($preset['attachments'])) {
        $email['attachments'] = $preset['attachments'];
      }

      $res[] = $email;
    }
    return $hasIngoing ? $res : null;
  }

  private static function isIngoing($addresses, $default)
  {
    if (!is_array($addresses)) {
      $addresses = [$addresses];
    }
    foreach ($addresses as $address) {
      if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
        return true;
      }
    }
    return $default;
  }

  private static function getAddresses($addresses, $data)
  {
    if (!is_array($addresses)) {
      $addresses = [$addresses];
    }
    $res = [];
    foreach ($addresses as $address) {
      if (!is_string($address) || strlen($address) === 0) {
        continue;
      }
      if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
        $res[] = $address;
      } else if (
        isset($data[$address]) &&
        filter_var($data[$address], FILTER_VALIDATE_EMAIL)
      ) {
        $res[] = $data[$address];
      }
    }
    if (count($res) === 0) {
      return null;
    } else if (count($res) === 1) {
      return $res[0];
    } else {
      return $res;
    }
  }

  private static function parseTemplate ($template, $data)
  {
    $html = kirby()->template('emails/' . $template, 'html', 'text');
    $text = kirby()->template('emails/' . $template, 'text', 'text');
    if ($html->exists()) {
      $body = [];
      $body['html'] = $html->render($data);
      if ($text->exists()) {
        $body['text'] = $text->render($data);
      }
      return $body;
    } elseif ($text->exists()) {
      return $text->render($data);
    }
    return null;
  }

  private static function buildInTemplate($data)
  {
    $res = "Automatically generated email\n\n";
    foreach ($data as $key => $value) {
      $res .= $key . ": " . $value . "\n";
    }
    return $res;
  }
}
