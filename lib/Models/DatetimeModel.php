<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;

/**
 * Model for Kirby's fields: date, time
 */
class DatetimeModel extends Model
{
  /**
   * Get type of this model as it's returned in response.
   * Method called by setModelData()
   * 
   * @return String 
   */
  protected function getType()
  {
    if ($this->blueprint->node('type')->get() === 'time') {
      return 'time';
    } else if($this->blueprint->node('time')->is(true)) {
      return 'datetime';
    } else {
      return 'date';
    }
  }

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
  protected function getProperties ()
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
    $meta->add('datetime', date('c', $time));
    $meta->add('jsdate', implode(',', $utc));
    $meta->add('timezone', date_default_timezone_get());

    $res = new Collection();
    $res->add('meta', $meta);
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   * 
   * @return String
   */
  protected function getValue ()
  {
    return $this->model->value();
  }
}
