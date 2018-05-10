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

use type Facebook\Markdown\Inlines\Inline;

class Heading extends LeafBlock {
  final public function __construct(
    private int $level,
    private vec<Inline> $heading,
  ) {
  }

  public function getLevel(): int {
    return $this->level;
  }

  public function getHeading(): vec<Inline> {
    return $this->heading;
  }
}
