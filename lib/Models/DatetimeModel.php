<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;

/**
 * Model for Kirby's fields: date, time
 */
class DatetimeModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties (): Collection
  {
    $time = strtotime($this->getValue());
    $type = $this->getType();
    if ($type === 'time') {
      $utc = [ 1970, 0, 1, (int) date('H', $time), (int) date('i', $time), (int) date('s', $time), 0 ];
    } else if ($type === 'date') {
      $utc = [ (int) date('Y', $time), (int) date('m', $time) - 1, (int) date('d', $time), 0, 0, 0, 0];
    } else {
      $utc = [(int) date('Y', $time), (int) date('m', $time) - 1, (int) date('d', $time),(int) date('H', $time), (int) date('i', $time), (int) date('s', $time), 0];
    }
    $res = new Collection();

    $timezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $res->add('utc', date('Y-m-d\TH:i:s\Z', $time));
    date_default_timezone_set($timezone);
    $res->add('iso', date('c', $time));
    $res->add('timezone', $timezone);
    return $res;
  }
  
  /**
   * Get type of this model as it's returned in response.
   */
  protected function getType(): string
  {
    if ($this->blueprint->node('type')->get() === 'time') {
      return 'time';
    } else if ($this->blueprint->node('time')->is(true)) {
      return 'datetime';
    } else {
      return 'date';
    }
  }

  /**
   * Get the value of model.
   */
  protected function getValue (): string
  {
    return (string) $this->model->value();
  }
}
