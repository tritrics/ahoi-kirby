<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;

/**
 * Model for Kirby's fields: date, time
 */
class DatetimeModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
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
    $meta = new Collection();
    $meta->add('iso', date('c', $time));
    // $meta->add('jsdate', implode(',', $utc)); JS: new Date(Date.UTC(...obj.meta.jsdate.split(','))),
    $meta->add('timezone', date_default_timezone_get());

    $res = new Collection();
    $res->add('meta', $meta);
    return $res;
  }
  
  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
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
   * Get the value of model as it's returned in response.
   * Mandatory method.
   */
  protected function getValue (): string
  {
    return (string) $this->model->value();
  }
}
