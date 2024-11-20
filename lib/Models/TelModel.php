<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\BaseModel;
use Tritrics\Ahoi\v1\Helper\LinkHelper;

/**
 * Model for Kirby's fields: tel
 */
class TelModel extends BaseModel
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
    $this->add('meta', LinkHelper::get($this->model->value(), null, false, null, 'tel'));
    $this->add('value', (string) $this->model->value());
  }
}