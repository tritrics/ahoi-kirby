<?php

namespace Tritrics\Api\Models;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;


/** */
class UserModel extends Model
{
  /** */
  protected $hasChildFields = true;

  /** */
  protected function getProperties ()
  {
    $meta = new Collection();
    $meta->add('id', md5($this->model->id()));

    $res = new Collection();
    $res->add('meta', $meta);
    return $res;
  }

  /**
   * For security-reasons we don't expose user's build-in values like
   * name, email, role, avatar. We only expose possibly extra-fields.
   */
  protected function getValue ()
  {
    return $this->fields;
  }
}