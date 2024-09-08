<?php

namespace Tritrics\Ahoi\v1\Controllers;

use Kirby\Http\Response as KirbyResponse;
use Tritrics\Ahoi\v1\Data\Response;
use Kirby\Exception\Exception;
use Tritrics\Ahoi\v1\Services\ActionService;
use Tritrics\Ahoi\v1\Helper\ConfigHelper;
use Tritrics\Ahoi\v1\Helper\RequestHelper;
use Tritrics\Ahoi\v1\Helper\TokenHelper;

/**
 * Options Controller
 */
class OptionsController
{
  /**
   * Options
   */
  public function options(): KirbyResponse
  {
    $Response = new Response('options');
    return $Response->get();
  }
}
