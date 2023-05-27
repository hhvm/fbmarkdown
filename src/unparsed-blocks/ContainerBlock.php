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

use namespace HH\Lib\Vec;

abstract class ContainerBlock<TChild as Block>
extends Block {
  public function __construct(
    protected vec<TChild> $children,
  ) {
  }

  public function getChildren(): vec<TChild> {
    return $this->children;
  }

  protected static function consumeChildren(
    Context $context,
    Lines $lines,
  ): vec<Block> {
    $children = vec[];
    while (!$lines->isEmpty()) {
      $pre_count = $lines->getCount();
      list($child, $lines) = self::consumeSingle($context, $lines);
      invariant(
        $pre_count > $lines->getCount(),
        'consuming failed to reduce line count with class "%s" on line "%s"',
        \get_class($child),
        $lines->getFirstLine(),
      );
      if ($child is BlockSequence) {
        $children = Vec\concat($children, $child->getChildren());
      } else {
        $children[] = $child;
      }
    }
    return $children;
  }

  final protected static function consumeSingle(
    Context $context,
    Lines $lines,
  ): (Block, Lines) {
    $context->pushParagraphContinuation(false);
    try {
      return self::consumeSingleImpl($context, $lines);
    } finally {
      $context->popParagraphContinuation();
    }
  }

  protected static function consumeSingleImpl(
    Context $context,
    Lines $lines,
  ): (Block, Lines) {
    $match = null;
    foreach ($context->getBlockTypes() as $block) {
      $match = $block::consume($context, $lines);
      if ($match !== null) {
        break;
      }
    }
    invariant($match !== null, 'should *always* find a match');
    return $match;
  }
}
