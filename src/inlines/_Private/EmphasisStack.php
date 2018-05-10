<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\Inlines\_Private\EmphasisStack;

use namespace Facebook\Markdown\Inlines;
use type Facebook\Markdown\Inlines\{Context, Inline};
use namespace HH\Lib\Str;

abstract class Node {
  public function __construct(
    private int $startOffset,
    private int $endOffset,
  ) {
  }

  final public function getStartOffset(): int {
    return $this->startOffset;
  }

  final public function getEndOffset(): int {
    return $this->endOffset;
  }

  abstract public function toInlines(Context $ctx): vec<Inline>;

  abstract public function debugDump(string $markdown): string;
}

class TextNode extends Node {
  public function __construct(
    private string $text,
    int $startOffset,
    int $endOffset,
  ) {
    invariant(
      $endOffset === $startOffset + Str\length($text),
      'Length mismatch: %d %d %d',
      $startOffset,
      Str\length($text),
      $endOffset,
    );
    parent::__construct($startOffset, $endOffset);
  }

  public function getText(): string {
    return $this->text;
  }

  <<__Override>>
  public function toInlines(Context $ctx): vec<Inline> {
    return Inlines\parse($ctx, $this->getText());
  }

  <<__Override>>
  public function debugDump(string $_markdown): string {
    return '(text '.\var_export($this->text, true).' '.
      $this->getStartOffset().'-'.$this->getEndOffset().')';
  }
}

class DelimiterNode extends TextNode {
  public function __construct(
    string $text,
    private int $flags,
    int $startOffset,
    int $endOffset,
  ) {
    parent::__construct($text, $startOffset, $endOffset);
  }

  public function getFlags(): int {
    return $this->flags;
  }

  <<__Override>>
  public function debugDump(string $_markdown): string {
    return '(delim '.
      (($this->flags & Inlines\Emphasis::IS_START) ? 'open' : '').
      (($this->flags & Inlines\Emphasis::IS_END) ? 'close' : '').
      ' '.\var_export($this->getText(), true).')';
  }
}

class InlineNode extends Node {
  public function __construct(
    private Inline $content,
    int $startOffset,
    int $endOffset,
  ) {
    parent::__construct($startOffset, $endOffset);
  }

  <<__Override>>
  public function toInlines(Context $_): vec<Inline> {
    return vec[$this->content];
  }

  <<__Override>>
  public function debugDump(string $_markdown): string {
    return '(inline '.\get_class($this->content).')';
  }
}

class EmphasisNode extends Node {
  public function __construct(
    private Inlines\Emphasis $content,
    int $startOffset,
    int $endOffset,
  ) {
    parent::__construct($startOffset, $endOffset);
  }

  public function getContent(): Inlines\Emphasis {
    return $this->content;
  }

  <<__Override>>
  public function toInlines(Context $_): vec<Inline> {
    return vec[$this->content];
  }

  public function getLength(): int {
    return ($this->getEndOffset() - $this->getStartOffset());
  }

  <<__Override>>
  public function debugDump(string $markdown): string {
    $node = $this->content;
    return '('.
      ($node->isStrong() ? 'strong' : 'em')
      .' '.
      \var_export(
        Str\slice($markdown, $this->getStartOffset(), $this->getLength()),
        true,
      )
      .' '
      .$this->getStartOffset().'-'.$this->getEndOffset()
      .')';
  }
}
