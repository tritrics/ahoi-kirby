<?php

namespace Tritrics\Ahoi\v1\Data;

use Kirby\Cms\Site;
use Kirby\Cms\Page;
use Kirby\Cms\File;
use Kirby\Cms\User;
use Kirby\Content\Field;
use Kirby\Cms\Block;
use Tritrics\Ahoi\v1\Data\Collection;

/**
 * Basic model for Kirby Fields and Models.
 * Inherits from Collection and adds some model functions.
 */
class BaseModel extends Collection
{
  /**
   * the Kirby Blueprint fragment
   * 
   * @var Collection
   */
  protected $blueprint;

  /**
   * Can be used, if model should output different content in different cases.
   */
  protected $addDetails = false;

  /**
   * Output control of child fields.
   * 
   * @var array
   */
  protected $addFields = [];

  /**
   * 2-digit Language-code
   * 
   * @var ?string
   */
  protected $lang;

  /**
   * the Kirby model instance
   * 
   * @var mixed
   */
  protected $model;

  /**
   */
  public function __construct (
    Block|Field|User|File|Page|Site|null $model = null,
    Collection $blueprint = null,
    string $lang = null,
    array $addFields = [],
    bool $addDetails = false
  ) {
    $this->model = $model;
    $this->blueprint = $blueprint instanceof Collection ? $blueprint : new Collection();
    $this->lang = $lang;
    $this->addFields = is_array($addFields) ? $addFields : [];
    $this->addDetails = $addDetails;
  }
}