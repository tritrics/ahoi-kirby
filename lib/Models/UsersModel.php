<?php

namespace Tritrics\AflevereApi\v1\Models;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Models\UserModel;
use Tritrics\AflevereApi\v1\Services\BlueprintService;

/**
 * Model for Kirby's fields: users
 */
class UsersModel extends Model
{
  /**
   * Get additional field data (besides type and value)
   * Method called by setModelData()
   * 
   * @return Collection 
   */
  protected function getProperties()
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
   * 
   * @return Collection
   */
  protected function getValue ()
  {
    $res = new Collection();
    foreach ($this->model->toUsers() as $user) {
      $blueprint = BlueprintService::getBlueprint($user);
      $model = new UserModel($user, $blueprint, $this->lang);
      $res->push($model);
    }
    return $res;
  }
}