<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;

/** */
class EmailModel extends Model
{
  /** */
  protected function getProperties ()
  {
    $res = new Collection();
    $link = $res->add('link');
    $link->add('type', 'email');
    $link->add('uri', 'mailto:' . $this->model->value());
    $link->add('title', $this->model->value());
    return $res;
  }

  /** */
  protected function getValue ()
  {
    return $this->model->value();
  }
}
