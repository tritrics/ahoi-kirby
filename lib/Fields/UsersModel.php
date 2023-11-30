<?php

namespace Tritrics\AflevereApi\v1\Fields;

use Tritrics\AflevereApi\v1\Data\Collection;
use Tritrics\AflevereApi\v1\Data\Model;
use Tritrics\AflevereApi\v1\Models\UserModel;
use Tritrics\AflevereApi\v1\Services\BlueprintService;

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