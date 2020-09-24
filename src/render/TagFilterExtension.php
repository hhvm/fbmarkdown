<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown;

use namespace HH\Lib\{Str, Keyset};

class TagFilterExtension extends RenderFilter {
  <<__Override>>
  public function filter(RenderContext $_context, ASTNode $node): vec<ASTNode> {
    if ($node is Blocks\HTMLBlock) {
      return vec[$this->filterHTMLBlock($node)];
    }
    if ($node is Inlines\RawHTML) {
      return vec[$this->filterInlineHTML($node)];
    }
    return vec[$node];
  }

  public function addToTagBlacklist(keyset<string> $toAdd): void {
    $this->blacklist = Keyset\union($this->blacklist, $toAdd);
  }

  protected function filterHTMLBlock(
    Blocks\HTMLBlock $block,
  ): Blocks\HTMLBlock {
    return new Blocks\HTMLBlock($this->filterHTML($block->getCode()));
  }

  protected function filterInlineHTML(
    Inlines\RawHTML $inline,
  ): Inlines\RawHTML {
    return new Inlines\RawHTML($this->filterHTML($inline->getContent()));
  }

  private keyset<string> $blacklist = keyset[
    '<title',
    '<textarea',
    '<style',
    '<xmp',
    '<iframe',
    '<noembed',
    '<noframes',
    '<script',
    '<plaintext',
  ];

  protected function filterHTML(string $code): string {
    foreach ($this->blacklist as $tag) {
      $offset = 0;
      while (true) {
        $offset = Str\search_ci($code, $tag, $offset);
        if ($offset === null) {
          break;
        }

        $code = Str\slice($code, 0, $offset).
          '&lt;'.
          Str\slice($code, $offset + 1);
        $offset += 3; // len('&lt;') - len('<')
      }
    }
    return $code;
  }
}
