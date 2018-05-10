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

function parse(
  ParserContext $context,
  string $markdown,
): Blocks\Document {
  $block_context = $context->getBlockContext();
  $inline_context = $context->getInlineContext();
  $no_inlines = UnparsedBlocks\parse($block_context, $markdown);
  $inline_context->setBlockContext($block_context);
  return $no_inlines->withParsedInlines($context->getInlineContext());
}
