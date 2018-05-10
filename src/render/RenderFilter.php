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

abstract class RenderFilter {
  abstract public function filter(
    RenderContext $context,
    ASTNode $node,
  ): vec<ASTNode>;

  public function resetFileData(): this {
    return $this;
  }
}
