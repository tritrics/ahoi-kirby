<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\Collection;
use Tritrics\Ahoi\v1\Helper\LinkHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;

/**
 * Model for Kirby's fields: link
 */
class LinkModel extends BaseModel
{
  /**
   * Link meta
   */
  private $meta = [];

  /**
   * Constructor with additional initialization.
   */
  public function __construct(mixed $model, ?Collection $blueprint, ?string $lang)
  {
    switch (LinkHelper::getType($model->value())) {
      case 'anchor':
        $this->meta = LinkHelper::getAnchor(
          $model->value(),
          preg_replace('/^(#)/', '', $model->value()),
          false
        );
        break;
      case 'email':
        $this->meta = LinkHelper::getEmail(
          $model->value(),
          preg_replace('/^(mailto:)/', '', $model->value()),
          false
        );
        break;
      case 'file':
        $file = KirbyHelper::findFileByKirbyLink($model->value());
        if ($file) {
          $this->meta = LinkHelper::getFile(
            $model->value(),
            (string) $file->title()->get(),
            true
          );
        }
        break;
      case 'page':
        $page = KirbyHelper::findPageByKirbyLink($model->value());
        if ($page) {
          $this->meta = LinkHelper::getPage(
            $model->value(),
            (string) $page->title()->get(),
            false,
            $this->lang
          );
        }
        break;
      case 'tel':
        $this->meta = LinkHelper::getTel(
          $model->value(),
          preg_replace('/^(tel:)/', '', $model->value()),
          false
        );
        break;
      case 'url':
        $this->meta = LinkHelper::getUrl(
          $model->value(),
          preg_replace('/^(http[s]*:\/\/)[.]*/', '', $model->value()),
          true
        );
        break;
      default:
        $this->meta = LinkHelper::getCustom(
          $model->value(),
          $model->value(),
          false
        );
        break;
    }
    parent::__construct($model, $blueprint, $lang);
  }

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $res->add('meta', $this->meta);
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue(): string
  {
    return $this->meta['title'] ?? '';
  }
}


