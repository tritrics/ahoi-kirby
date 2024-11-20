<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\BaseModel;

/**
 * Model for Kirby's fields: date, time
 */
class DatetimeModel extends BaseModel
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
    if ($this->blueprint->node('type')->get() === 'time') {
      $this->add('type', 'time');
      // $utc = [1970, 0, 1, (int) date('H', $time), (int) date('i', $time), (int) date('s', $time), 0];
    } else if ($this->blueprint->node('time')->is(true)) {
      $this->add('type', 'datetime');
      // $utc = [(int) date('Y', $time), (int) date('m', $time) - 1, (int) date('d', $time), (int) date('H', $time), (int) date('i', $time), (int) date('s', $time), 0];
    } else {
      $this->add('type', 'date');
      // $utc = [(int) date('Y', $time), (int) date('m', $time) - 1, (int) date('d', $time), 0, 0, 0, 0];
    }
    
    $time = strtotime($this->model->value());
    $timezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $this->add('utc', date('Y-m-d\TH:i:s\Z', $time));
    date_default_timezone_set($timezone);
    $this->add('iso', date('c', $time));
    $this->add('timezone', $timezone);
    $this->add('value', (string) $this->model->value());
  }
}
