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
    $res = new Collection();
    $res->add('id', md5($this->model->id()));
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