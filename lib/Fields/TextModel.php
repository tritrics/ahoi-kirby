<?php

namespace Tritrics\Api\Fields;

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
   * | writer       | inline: true           | html       | <br>              | html           |
   * |              | or nor breaks in text  |            |                   | without elem   |
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
        $this->type = 'html';
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
    if ($this->type !== 'html') {
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

    // make HTML editabel and get as array
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadHTML('
      <!DOCTYPE html>
      <html>
        <head>
          <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        </head>
        <body>' . $buffer . '</body>
      </html>'
    );
    $nodelist = $dom->getElementsByTagName('body');
    $res = $this->htmlToArray($nodelist->item(0));
    unset($res['elem']); // body

    if (isset($res['children'])) {
      if (count($res['children']) === 1) {
        $res = array_shift($res['children']);
      } else {
        $res = $res['children'];
      }
    }

    /**
     * $res can be:
     * 
     * 1. a simple text
     * { text: 'the text' }
     * 
     * 2. a single block-element
     * { elem: 'h1', text: 'the text' }
     * 
     * 3. an array with more than one of the above where every
     *    possible sub-element is in node children
     * [ { elem: 'h1', text: 'the text' }, { elem: 'p', children: [] }]
     */
    return $res;
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
      $res = [ 'elem' => strtolower($root->nodeName) ];
      if ($root->hasChildNodes()) {
        $res['children'] = [];
        $children = $root->childNodes;
        for ($i = 0; $i < $children->length; $i++) {
          $child = $this->htmlToArray($children->item($i));
          if (!empty($child)) {
            $res['children'][] = $child;
          }
        }

        // if it's only a block-element with simple text, then remove children
        // <h1>Headline</h1> => [ 'elem' => 'h1', 'text' => 'Headline' ]
        if (count($res['children']) === 1 && count($res['children'][0]) === 1 && isset($res['children'][0]['text'])) {
          $res['text'] = $res['children'][0]['text'];
          unset($res['children']);
        }
      }

      // add attributes as optional 3rd entry
      if ($root->hasAttributes()) {
        $res['attr'] = [];
        foreach ($root->attributes as $attribute) {
          $res['attr'][$attribute->name] = $attribute->value;
        }

        // change attributes, if it's a link
        if ($res['elem'] === 'a') {
          $res['attr'] = LinkService::get(
            $this->lang,
            $res['attr']['href'],
            (isset($res['attr']['title']) ? $res['attr']['title'] : null),
            (isset($res['attr']['target']) && $res['attr']['target'] === '_blank')
          );
        }
      }
      return $res;
    }

    // text node
    if ($root->nodeType == XML_TEXT_NODE || $root->nodeType == XML_CDATA_SECTION_NODE) {
      $value = $root->nodeValue;
      if (!empty($value)) {
        return ['text' => $value];
      }
    }
  }
}