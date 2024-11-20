<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\User;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\BlueprintHelper;

/**
 * Model for Kirby's fields: users
 */
class UsersModel extends BaseEntriesModel
{
  /**
   * Constructor with additional initialization.
   */
  public function __construct() {
    parent::__construct(...func_get_args());
    $this->setEntries($this->model->toUsers());
    $this->setData();
  }

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
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'users');

    // meta
    $meta = $this->add('collection');
    $meta->add('count', $this->entries->count());

    // entries
    $entries = $this->add('entries');
    foreach ($this->entries as $user) {
      $blueprint = BlueprintHelper::get($user);
      $model = $this->createEntry($user, $blueprint, $this->lang, $this->addFields);
      $entries->push($model);
    }
  }
}