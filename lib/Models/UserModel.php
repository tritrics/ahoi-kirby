<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;

/**
 * Model for Kirby's user object
 */
class UserModel extends BaseModel
{
  /**
   * Marker if this model has child fields.
   * 
   * @var bool
   */
  protected $hasChildFields = true;

  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties (): Collection
  {
    $meta = new Collection();
    $meta->add('id', md5($this->model->id()));

    $res = new Collection();
    $res->add('meta', $meta);
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * For security-reasons we don't expose user's build-in values like
   * name, email, role, avatar. We only expose possibly extra-fields.
   */
  protected function getValue (): Collection
  {
    return $this->fields;
  }
}