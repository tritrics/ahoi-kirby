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
   * Linktype, intern use
   * 
   * @var string [http, https, page, file, email, tel, anchor, custom]
   */
  private $linktype;

  /**
   * Constructor with additional initialization.
   */
  public function __construct(mixed $model, ?Collection $blueprint, ?string $lang)
  {
    $this->linktype = LinkHelper::getType($model->value());
    parent::__construct($model, $blueprint, $lang);
  }

  /**
   * Get additional field data (besides type and value)
   */
  protected function getProperties(): Collection
  {
    $res = new Collection();
    $res->add('meta', LinkHelper::get(
      $this->model->value(),
      null,
      false,
      $this->lang,
      $this->linktype
    ));
    return $res;
  }

  /**
   * Get the value of model.
   */
  protected function getValue(): string
  {
    switch ($this->linktype) {
      case 'anchor':
        return preg_replace('/^(#)/', '', $this->model->value());
      case 'email':
        return preg_replace('/^(mailto:)/', '', $this->model->value());
      case 'file':
        $model = KirbyHelper::findFileByKirbyLink($this->model->value());
        return $model ? (string) $model->title()->get() : '';
      case 'page':
        $model = KirbyHelper::findPageByKirbyLink($this->model->value());
        return $model ? (string) $model->title()->get() : '';
      case 'tel':
        return preg_replace('/^(tel:)/', '', $this->model->value());
      case 'url':
        return preg_replace('/^(http[s]*:\/\/)[.]*/', '', $this->model->value());
      default:
        return $this->model->value();
    }
  }
}


