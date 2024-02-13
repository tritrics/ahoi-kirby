<?php

namespace Tritrics\AflevereApi\v1\Helper;

/**
 * Helper to create and check action token.
 */
class TokenHelper
{
  /**
   * Helper to decode url encoded base64 string.
   */
  private static function base64UrlDecode(string $str): string
  {
    $base64 = str_replace(['-', '_'], ['+', '/'], $str);
    $pad = strlen($base64) % 4;
    $base64 .= str_repeat('=', $pad);
    return base64_decode($base64);
  }

  /**
   * Helper to encode url encoded base64 string.
   */
  private static function base64UrlEncode(string $str): string
  {
    $base64 = base64_encode($str);
    return str_replace(['+', '/', '='], ['-', '_', ''], $base64);
  }

  /**
   * Checking the token, reverse of getToken().
   * Checks for same action and expiration time.
   */
  public static function check(string $action, string $token): bool
  {
    list($payloadEnc, $signatureEnc) = explode('.', (string) $token);
    if (!is_string($payloadEnc) || !is_string($signatureEnc)) {
      return false;
    }

    // check signature
    if (self::getSignature($payloadEnc) !== self::base64UrlDecode($signatureEnc)) {
      return false;
    }

    // check data
    $payload   = self::base64UrlDecode($payloadEnc);
    $data = json_decode($payload, true);
    if ($data === null || !isset($data['exp']) || !isset($data['act'])) {
      return false;
    }
    return $data['act'] === $action && (int) $data['exp'] >= time();
  }

  /**
   * Getting a form token which is x sec valid.
   * @see: https://dev.to/robdwaller/how-to-create-a-json-web-token-using-php-3gml
   * @see: https://github.com/RobDWaller/ReallySimpleJWT
   */
  public static function get(string $action): ?string
  {
    // Payload
    $payload = json_encode([
      'exp' => time() + ConfigHelper::getConfig('form-security.token-validity', 10),
      'act' => $action,
    ]);
    $payloadEnc = self::base64UrlEncode($payload);

    // Signature
    $signature = self::getSignature($payloadEnc);
    if ($signature === null) {
      return null;
    }
    $signatureEnc = self::base64UrlEncode($signature);

    // Token
    return $payloadEnc . '.' . $signatureEnc;
  }

  /**
   * Get the secret from config.
   * min. 12 chars, containing upper, lower, numbers and #?!@$%^&*-
   */
  public static function getSecret(): ?string
  {
    $secret = (string) ConfigHelper::getConfig('form-security.secret');
    preg_match("/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{12,}$/", $secret, $check);
    if (is_array($check) && count($check) === 1 && $check[0] === $secret) {
      return $secret;
    }
    return null;
  }

  /**
   * Getting the signature, encoded with secret from config.
   */
  private static function getSignature(string $payloadEnc): ?string
  {
    $secret = self::getSecret();
    if ($secret === null) {
      return null;
    }
    return hash_hmac('sha256', $payloadEnc, $secret, true);
  }

  /**
   * Check if config has valid secret.
   */
  public static function hasSecret(): bool
  {
    return self::getSecret() !== null;
  }
}
