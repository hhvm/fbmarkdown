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

use type Facebook\Markdown\Blocks\Heading as ASTHeading;
use namespace Facebook\Markdown\Inlines;

use namespace HH\Lib\{Regex, Str};

class SetextHeading extends LeafBlock implements BlockProducer {
  public function __construct(private int $level, private string $heading) {
  }

  public static function consume(
    Context $context,
    Lines $lines,
  ): ?(Block, Lines) {
    if ($context->isInParagraphContinuation()) {
      return null;
    }

    list($first_col, $first, $lines) = $lines->getColumnFirstLineAndRest();
    $heading = $first;

    while (!$lines->isEmpty()) {
      list($col, $line, $rest) = $lines->getColumnFirstLineAndRest();
      if (Lines::isBlankLine($line)) {
        return null;
      }

      $matches = Regex\first_match($line, re"/^ {0,3}(?<level>=+|-+) *$/");
      if ($matches is nonnull) {
        // Heading underline can not be a lazy continuation item
        if ($col >= $first_col) {
          $level = $matches['level'][0] === '=' ? 1 : 2;
          return tuple(
            new self($level, Str\trim($heading)),
            $rest,
          );
        }
      }

      if (!_Private\is_paragraph_continuation_text($context, $lines)) {
        return null;
      }
      $heading .= "\n".$line;
      $lines = $rest;
    }
    return null;
  }

  <<__Override>>
  public function withParsedInlines(Inlines\Context $ctx): ASTHeading {
    return new ASTHeading(
      $this->level,
      Inlines\parse($ctx, $this->heading),
    );
  }
}
