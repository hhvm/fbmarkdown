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

use type Facebook\Markdown\Inlines\Context as InlineContext;
use type Facebook\Markdown\Blocks\Block as ASTBlock;

abstract class Block {
  abstract public function withParsedInlines(InlineContext $_): ASTBlock;
}
