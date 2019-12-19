<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\UnparsedBlocks;

use namespace HH\Lib\{Regex, Str};
use type Facebook\Markdown\Blocks\Heading as ASTHeading;
use namespace Facebook\Markdown\Inlines;

class ATXHeading extends LeafBlock implements BlockProducer {

  public function __construct(private int $level, private string $heading) {
  }

  public static function consume(
    Context $_context,
    Lines $lines,
  ): ?(Block, Lines) {
    $patterns = vec[
      re"/^ {0,3}(?<level>#{1,6})([ \\t](?<title>.*))?[ \\t]+#+[ \\t]*$/",
      re"/^ {0,3}(?<level>#{1,6})([ \\t](?<title>.*))?$/",
    ];

    list($first, $rest) = $lines->getFirstLineAndRest();

    $title = null;
    $level = null;
    foreach ($patterns as $pattern) {
      $matches = Regex\first_match($first, $pattern);
      if ($matches is nonnull) {
        $title = $matches['title'] ?? '';
        $level = Str\length($matches['level']);
        break;
      }
    }

    if ($title is null || $level is null) {
      return null;
    }

    return tuple(new self($level, Str\trim($title)), $rest);
  }

  <<__Override>>
  public function withParsedInlines(Inlines\Context $ctx): ASTHeading {
    return new ASTHeading(
      $this->level,
      Inlines\parse($ctx, $this->heading),
    );
  }
}
