<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\User;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's fields: users
 */
class UsersModel extends BaseModel
{
  /**
   * Nodename for users.
   */
  protected $valueNodeName = 'entries';

  /**
   * Create a child entry instance
   */
  public function createEntry(
    User $model = null,
    Collection $blueprint = null,
    string $lang = null,
    array $addFields = []
  ): Collection {
    return new UserModel($model, $blueprint, $lang, $addFields);
  }
  
  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $meta = $res->add('collection');
    $meta->add('count', $this->model->toUsers()->count());
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue (): Collection
  {
    $res = new Collection();
    foreach ($this->model->toUsers() as $user) {
      $blueprint = BlueprintHelper::get($user);
      $model = $this->createEntry($user, $blueprint, $this->lang, $this->addFields);
      $res->push($model);
    }
    return $res;
  }
}