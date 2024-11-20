<?php

namespace Tritrics\Ahoi\v1\Models;

/**
 * Model for Kirby's fields: color
 */
class ColorModel extends BaseModel
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
    $this->add('type', 'color');
    if ($this->blueprint->has('format')) {
      $this->add('format', $this->blueprint->node('format')->get());
    } else {
      $this->add('format', 'hex');
    }
    if ($this->blueprint->has('format')) {
      $this->add('alpha', $this->blueprint->node('alpha')->get());
    } else {
      $this->add('alpha', false);
    }
    $this->add('value', (string) $this->model->value());
  }
}
