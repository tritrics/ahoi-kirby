<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's fields: users
 */
class UsersModel extends BaseModel
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $meta = $res->add('meta');
    $meta->add('multiple', $this->isMultiple());
    $meta->add('count', $this->model->toUsers()->count());
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   * Mandatory method.
   */
  protected function getValue (): Collection
  {
    $res = new Collection();
    foreach ($this->model->toUsers() as $user) {
      $blueprint = BlueprintHelper::getBlueprint($user);
      $model = new UserModel($user, $blueprint, $this->lang);
      $res->push($model);
    }
    return $res;
  }
}