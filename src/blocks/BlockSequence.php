<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\Blocks;

use namespace HH\Lib\Vec;

// Not used by the core engine; useful for extensions when a single piece
// of syntax might want to create multiple blocks
final class BlockSequence extends LeafBlock {
  private vec<Block> $children;

  public function __construct(
    vec<?Block> $children,
  ) {
    $this->children = Vec\filter_nulls($children);
  }

  public static function flatten(?Block ...$children): this {
    return new self(vec($children));
  }

  public function getChildren(): vec<Block> {
    return $this->children;
  }
}
