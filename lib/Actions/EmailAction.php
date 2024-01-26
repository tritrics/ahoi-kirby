<?php

namespace Tritrics\AflevereApi\v1\Actions;

use Exception;
use Throwable;
use Kirby\Exception\LogicException;
use Tritrics\AflevereApi\v1\Services\GlobalService;

/**
 * Sending E-Mails
 */
class EmailAction
{
  /**
   * Sending emails defined in $presets.
   * 
   * @param Array $emails configuration from config actions.emails
   * @param Array $data the form data to be parsed into the email template
   * @param String $lang 2-char lang code
   * @return Array result protocol 
   * @throws Exception 
   */
  public static function send($presets, $data, $lang, $checkInbound = false)
  {
    $res = [
      'total' => 0,
      'success' => 0,
      'fail' => 0,
      'errno' => 0,
    ];

    $hosts = GlobalService::getHosts($lang);

    // Computing Meta
    $meta = [];
    $meta['__date__'] = date('Y-m-d');
    $meta['__time__'] = date('H:i:s');
    $meta['__host__'] = $hosts['referer']['host'];
    $meta['__ip__']   = $hosts['referer']['ip'];
    if (is_string($lang)) {
      $meta['__lang__'] = $lang;
    }

    // computing the mails from $presets
    $emails = self::getEmails($presets, $lang, $data, $meta, $hosts);
    $res['total'] = is_array($emails) ? count($emails) : 0;

    // @errno20: All mail configurations in config.php are invalid, nothing to send.
    if ($res['total'] === 0) {
      $res['errno'] = 20;
      return $res;
    }

    // @errno21: No valid inbound mail action configured in config.php.
    if ($checkInbound) {
      $count = 0;
      foreach($emails as $email) {
        if ($email['inbound']) {
          $count++;
        }
      }
      if ($count === 0) {
        $res['errno'] = 21;
        return $res;
      }
    }

    // sending
    $inboundSent = 0;
    foreach ($emails as $email) {
      try {
        if (kirby()->email($email)->isSent()) {
          $res['success']++;
          if ($email['inbound']) {
            $inboundSent++;
          }
        } else {
          $res['fail']++;
        }
      } catch(Exception $E) {
        $res['fail']++;
      }
    }

    // @errno22: Sending failed for all inbound mails.
    if ($inboundSent === 0) {
      $res['errno'] = 22;
    }

    // @errno200: Error on sending %fail from %total mails.
    else if ($res['fail'] > 0) {
      $res['errno'] = 200;
    }

    // OK
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
  private static function getEmails($presets, $lang, $formdata, $metadata, $hosts)
  {
    $res = [];
    foreach ($presets as $preset) {

      // first compute data
      // form data overwrites preset data overwrites meta data
      $data = $metadata;
      if (isset($preset['data']) && is_array($preset['data'])) {
        $data = array_merge($data, $preset['data']);
      }
      if (is_array($formdata)) {
        $data = array_merge($data, $formdata);
      }

      // build array with email config, like required by Kirby's mail function
      $email = [ 'inbound' => false ];

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
        if (!$email['inbound']) {
          $email['inbound'] = self::isInbound($preset['to']);
        }
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
        if (!$email['inbound']) {
          $email['inbound'] = self::isInbound($preset['cc']);
        }
      }

      // bcc optional
      if (isset($preset['bcc'])) {
        $email['bcc'] = self::getAddresses($preset['bcc'], $data);
        if ($email['bcc'] === null) {
          unset($email['bcc']);
        }
        if (!$email['inbound']) {
          $email['inbound'] = self::isInbound($preset['bcc'], );
        }
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
      if (isset($preset['attachments']) and is_array($preset['attachments'])) {
        $email['attachments'] = self::checkAttachments($preset['attachments']);
      }

      $res[] = $email;
    }
    return $res;
  }

  /**
   * If to, cc or bcc have at least one valid email address in config,
   * the mail is considered to be inbound. Mail actions which don't send
   * any inbound mails are in some cases skipped.
   * 
   * @param String|Array $addresses
   * @return Boolean 
   */
  private static function isInbound($addresses)
  {
    if (!is_array($addresses)) {
      $addresses = [$addresses];
    }
    foreach ($addresses as $address) {
      if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check if addresses are valid mail adresses or a field name,
   * so the mail adress is taken from data.
   * 
   * @param String|Array $addresses 
   * @param Array $data 
   * @return Null|String|Array 
   */
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

  /**
   * Read a template an parse data in.
   * Supports both text and html templates.
   * 
   * @param String $template 
   * @param Array $data 
   * @return String|null 
   * @throws Throwable 
   * @throws LogicException 
   */
  private static function parseTemplate($template, $data)
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

  /**
   * Simple list with values as mail body in case a template is missing.
   * 
   * @param mixed $data 
   * @return string 
   */
  private static function buildInTemplate($data)
  {
    $meta = '';
    $body = '';
    foreach ($data as $key => $value) {
      preg_match('/__([a-zA-Z0-9]+)__/', $key, $found);
      if (count($found) === 2) {
        $meta .= ucfirst($found[1]) . ": " . $value . "\n";
      } else if (strstr($value, PHP_EOL)) {
        $body .= $key . ": ¬\n\n" . $value . "\n\n";
      } else {
        $body .= $key . ": " . $value . "\n";
      }
    }
    $res = "Automatically generated email\n";
    if ($meta) {
      $res .= "\n" . $meta;
    }
    if ($body) {
      $res .= "\n" . $body;
    }
    return $res . "\n";
  }

  /**
   * Check if attachment file is existing.
   * Given paths must be abolute or relative inside Kirby root. All non-existing files are
   * stripped, because otherwise PHPMailer will fail to send the mail.
   * 
   * @param Array $paths 
   * @return Array
   */
  private static function checkAttachments($paths)
  {
    $root = rtrim(kirby()->root(), '/') . '/';
    $res = [];
    foreach ($paths as $path) {

      // we don't support Models or anything else
      if (!is_string($path) || strlen($path) === 0) {
        continue;
      }

      // relative path, must be inside Kirby root
      if (substr($path, 0, 1) !== '/') {
        $path = $root . $path;
      }
      if (is_file($path)) {
        $res[] = $path;
      }
    }
    return $res;
  }
}
