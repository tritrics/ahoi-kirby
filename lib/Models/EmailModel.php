<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Helper\LinkHelper;

/**
 * Model for Kirby's fields: email
 */
class EmailModel extends BaseModel
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
    $this->add('meta', LinkHelper::get($this->model->value(), null, false, null, 'email'));
    $this->add('value', (string) $this->model->value());
  }
}
