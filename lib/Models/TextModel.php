<?php

namespace Tritrics\Api\Models;

use Collator;
use Tritrics\Api\Data\Model;
use Tritrics\Api\Services\LinkService;

/** */
class TextModel extends Model
{
  /** */
  private $fieldtype;

  /** */
  private $type;

  /**
   * Possible Texttypes:
   * 
   * -------------------------------------------------------------------------------------------
   * | FIELD-TYPE   | FIELD-DEF              | FORMATTING | LINEBREAKS        | API-TYPE       | 
   * |--------------|------------------------|------------|-------------------|----------------|
   * | text, slug   |                        | ./.        | ./.               | text           |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | textarea     | buttons: false         | ./.        | \n                | text-multiline |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | textarea     | buttons: true          | markdown   | \n                | markdown       |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | textarea     | buttons: false|true    | html       | <blocks>          | html           |
   * |              | api: html: true        |            | <br>              |                |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | writer, list | inline: false          | html       | <blocks>          | html           |
   * |              |                        |            | <br>              |                |
   * |--------------|------------------------|------------|-------------------|----------------|
   * | writer       | inline: true           | html       | <br>              | html-inline    |
   * |              |                        |            | no wrapping block |                |
   * -------------------------------------------------------------------------------------------
   * 
   * Textarea parsing as html is provided for older Kirby-projects, where writer-field was
   * not existing. In new projects writer should be used for html and textarea for text/markdown.
   * The possible combination: $textarea->kirbytext()->inline() is not provided, because the
   * field-buttons contains the block-elements headline and lists. These blocks are
   * stripped out by inline() which only makes sense, when the buttons are configured without.
   */
  protected function getType ()
  {
    // set properties
    $this->fieldtype = $this->blueprint->node('type')->get();

    switch ($this->fieldtype) {
      case 'textarea':
        if ($this->blueprint->node('api', 'html')->is(true)) {
          $this->type = 'html';
        } elseif ($this->blueprint->node('buttons')->is(false)) {
          $this->type = 'text-multiline';
        } else {
          $this->type = 'markdown';
        }
        break;
      case 'list':
        $this->type = 'html';
        break;
      case 'writer':
        if ($this->blueprint->node('inline')->is(true)) {
          $this->type = 'html-inline';
        } else {
          $this->type = 'html';
        }
        break;
      default: // text, slug
        $this->type = 'text';
        break;
    }
    return $this->type;
  }

  /** */
  protected function getValue ()
  {
    if ($this->type !== 'html' && $this->type !== 'html-inline') {
      return '' . $this->model->value();
    }

    if ($this->fieldtype === 'textarea') {
      $buffer = $this->model->kirbytext();
    } else if ($this->fieldtype === 'writer') {
      $buffer = $this->model->text();
    } else if ($this->fieldtype === 'list') {
      $buffer = $this->model->list();
    } else { // error
      return '';
    }

      // delete line breaks
    $buffer = str_replace(["\n", "\r", "\rn"], "", $buffer);

    // delete special elements
    $buffer = str_replace(["<figure>", "</figure>"], "", $buffer);

    // make HTML editabel
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadHTML('<!DOCTYPE html><html><head></head><body>' . $buffer . '</body></html>');
    $nodelist = $dom->getElementsByTagName('body');
    $body = $nodelist->item(0);

    // get html as array
    $html = $this->htmlToArray($body);
    if (isset($html[1]) && is_array($html[1])) { // top-elem is <body> we don't need
      $html = $html[1];
    } else {
      $html = [];
    }
    return $html;
  }

  /**
   * Credits to https://gist.github.com/yosko/6991691
   * @param mixed $root 
   * @return mixed 
   */
  function htmlToArray($root)
  {
    // node with nodetype
    if ($root->nodeType == XML_ELEMENT_NODE) {
      $res = [ strtolower($root->nodeName) ];
      if ($root->hasChildNodes()) {
        $res[1] = [];
        $children = $root->childNodes;
        for ($i = 0; $i < $children->length; $i++) {
          $child = $this->htmlToArray($children->item($i));
          if (!empty($child)) {
            $res[1][] = $child;
          }
        }
        if (count($res[1]) === 1) {
          $res[1] = $res[1][0];
        }
      }

      // add attributes as optional 3rd entry
      if ($root->hasAttributes()) {
        $res[2] = [];
        foreach ($root->attributes as $attribute) {
          $res[2][$attribute->name] = $attribute->value;
        }

        // change attributes, if it's a link
        if ($res[0] === 'a') {
          $res[2] = LinkService::get(
            $this->lang,
            $res[2]['href'],
            (isset($res[2]['title']) ? $res[2]['title'] : null),
            (isset($res[2]['target']) && $res[2]['target'] === '_blank')
          );
        }
      }
      return $res;
    }

    // text node
    if ($root->nodeType == XML_TEXT_NODE || $root->nodeType == XML_CDATA_SECTION_NODE) {
      $value = $root->nodeValue;
      if (!empty($value)) {
        return $value;
      }
    }
  }
}