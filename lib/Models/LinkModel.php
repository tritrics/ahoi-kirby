<?php

namespace Tritrics\Ahoi\v1\Models;

use Tritrics\Ahoi\v1\Data\BaseModel;
use Tritrics\Ahoi\v1\Helper\LinkHelper;
use Tritrics\Ahoi\v1\Helper\KirbyHelper;

/**
 * Model for Kirby's fields: link
 */
class LinkModel extends BaseModel
{
  /**
   */
  public function __construct()
  {
    parent::__construct(...func_get_args());
    $this->setData();
  }

  /**
   * Set model data.
   */
  private function setData(): void
  {
    $this->add('type', 'link');

    // meta
    switch (LinkHelper::getType($this->model->value())) {
      case 'anchor':
        $this->add('meta', LinkHelper::getAnchor(
          $this->model->value(),
          preg_replace('/^(#)/', '', $this->model->value()),
          false
        ));
        break;
      case 'email':
        $this->add('meta', LinkHelper::getEmail(
          $this->model->value(),
          preg_replace('/^(mailto:)/', '', $this->model->value()),
          false
        ));
        break;
      case 'file':
        $file = KirbyHelper::findFileByKirbyLink($this->model->value());
        $title = $file ? (string) $file->title()->get() : '';
        $this->add('meta', LinkHelper::getFile(
           $this->model->value(),
          $title,
          true
        ));
        break;
      case 'page':
        $page = KirbyHelper::findPageByKirbyLink($this->model->value());
        $title = $page ? (string) $page->title()->get() : '';
        $this->add('meta', LinkHelper::getPage(
          $this->model->value(),
          $title,
          false,
          $this->lang
        ));
        break;
      case 'tel':
        $this->add('meta', LinkHelper::getTel(
          $this->model->value(),
          preg_replace('/^(tel:)/', '', $this->model->value()),
          false
        ));
        break;
      case 'url':
        $this->add('meta', LinkHelper::getUrl(
          $this->model->value(),
          preg_replace('/^(http[s]*:\/\/)[.]*/', '', $this->model->value()),
          true
        ));
        break;
      default:
        $this->add('meta', LinkHelper::getCustom(
          $this->model->value(),
          $this->model->value(),
          false
        ));
        break;
    }
    $value = $this->node('meta', 'title')->get();
    $this->add('value', is_string($value) ? $value : '');
  }
}


