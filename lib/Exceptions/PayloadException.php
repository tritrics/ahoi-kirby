<?php

namespace Tritrics\AflevereApi\v1\Exceptions;

use Exception;

/**
 * The name says it all.
 */
class PayloadException extends Exception
{
  /**
   * The additional data
   * 
   * @var mixed
   */
  private $payload;

  /**
   */
  public function __construct(string $message, int $code, mixed $payload = null)
  {
    $this->payload = $payload;
    parent::__construct($message, $code);
  }

  /**
   * Get the payload.
   */
  public function getPayload (): mixed
  {
    return $this->payload;
  }
}