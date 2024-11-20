<?php

namespace Tritrics\Ahoi\v1\Models;

/**
 * Model for Kirby's user object
 */
class UserModel extends BaseFieldsModel
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
    $this->add('type', 'user');

    // empty model, for empty none-multiple-collections
    if (!$this->model) {
      return;
    }
    $meta = $this->add('meta');
    $meta->add('id', md5($this->model->id()));

    // fields
    if ($this->fields->count() > 0) {
      $this->add('fields', $this->fields);
    }
  }
}