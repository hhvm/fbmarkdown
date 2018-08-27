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


abstract class ContainerBlock extends Block {
  public function __construct(
    protected vec<Block> $children,
  ) {
  }

  final public function getChildren(): vec<Block> {
    return $this->children;
  }
}
