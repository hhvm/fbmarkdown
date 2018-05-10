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

class ListItem extends ContainerBlock {
  public function __construct(
    private ?int $number,
    vec<Block> $children,
  ) {
    parent::__construct($children);
  }

  final public function getNumber(): ?int {
    return $this->number;
  }
}
