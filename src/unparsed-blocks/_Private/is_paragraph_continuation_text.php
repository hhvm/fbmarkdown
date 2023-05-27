<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\UnparsedBlocks\_Private;

use type Facebook\Markdown\UnparsedBlocks\{BlockProducer, Context, Lines};
use namespace HH\Lib\C;

function is_paragraph_continuation_text(
  Context $context,
  Lines $lines,
): bool {
  $context->pushParagraphContinuation(true);
  try {
    return !C\any(
      $context->getBlockTypes(),
      (classname<BlockProducer> $block) ==> $block::consume($context, $lines) !== null,
    );
  } finally {
    $context->popParagraphContinuation();
  }
}
