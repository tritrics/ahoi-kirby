<?php

namespace Tritrics\Ahoi\v1\Models;

use Kirby\Cms\Blocks;
use Kirby\Cms\Files;
use Kirby\Cms\Pages;
use Kirby\Cms\Structure;
use Kirby\Cms\Users;
use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\TypeHelper;

/**
 * Basic model for Kirby Fields and Models with children/entries.
 */
class BaseEntriesModel extends BaseModel
{
  /**
   * The children/entries (models).
   */
  protected $entries;

  /**
   */
  public function __construct()
  {
    parent::__construct(...func_get_args());
  }

  /**
   * Create a child entry instance, is overwritten by collection classes
   */
  public function createEntry(): Collection
  {
    return new Collection();
  }

  /**
   * Get first child of collection, if there is any.
   * Used for collections with setting multiple: false
   */
  public function getFirstEntry(): Collection|null
  {
    // not $this->entries, used AFTER $this->entries are added to nodes.
    if ($this->node('entries')->isCollection() && $this->node('entries')->has(0)) {
      return $this->node('entries')->first();
    }
    return null;
  }

  /**
   * Check if this model is a collection with only one child allowed
   * due to blueprint definition multiple: false.
   */
  public function isSingleEntry(): bool
  {
    return
      $this->blueprint->has('multiple') &&
      TypeHelper::isFalse($this->blueprint->node('multiple')->get());
  }

  /**
   * Setter for entries(-models).
   */
  protected function setEntries(Blocks|Files|Pages|Structure|Users|array $entries): void
  {
    $this->entries = $entries;
  }
}
