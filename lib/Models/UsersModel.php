<?php

namespace Tritrics\Tric\v1\Models;

use Tritrics\Tric\v1\Data\Collection;
use Tritrics\Tric\v1\Helper\BlueprintHelper;

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
    $meta = $res->add('collection');
    $meta->add('multiple', $this->isMultiple());
    $meta->add('count', $this->model->toUsers()->count());
    return $res;
  }

  /**
   * Get the value of model as it's returned in response.
   */
  protected function getValue (): Collection
  {
    $addFields = []; // no fields added on default, must be explizit set.
    if ($this->blueprint->node('api')->has('fields')) {
      $addFields = $this->blueprint->node('api')->node('fields')->get();
    }
    $res = new Collection();
    foreach ($this->model->toUsers() as $user) {
      $blueprint = BlueprintHelper::getBlueprint($user);
      $model = new UserModel($user, $blueprint, $this->lang, $addFields);
      $res->push($model);
    }
    return $res;
  }
}