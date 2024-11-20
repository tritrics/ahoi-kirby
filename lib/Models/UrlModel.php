<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\BaseModel;
use Tritrics\Ahoi\v1\Helper\LinkHelper;

/**
 * Model for Kirby's fields: url
 */
class UrlModel extends BaseModel
{
  /**
   */
  public function __construct()
  {
    parent::__construct(...func_get_args());
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'link');
    $this->add('meta', LinkHelper::get($this->model->value(), null, false, null, 'url'));
    $this->add('value', (string) $this->model->value());
  }
}
