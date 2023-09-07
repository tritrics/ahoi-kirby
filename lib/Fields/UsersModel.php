<?php

namespace Tritrics\Api\Fields;

use Tritrics\Api\Data\Collection;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Models\UserModel;
use Tritrics\Api\Services\BlueprintService;

/** */
class UsersModel extends Model
{
  /** */
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