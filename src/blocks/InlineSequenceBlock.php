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

use namespace Facebook\Markdown\Inlines;
use namespace HH\Lib\Vec;

/** Not used by the core engine; useful for extensions that want to create
 * markdown text. */
final class InlineSequenceBlock extends LeafBlock {
  private vec<Inlines\Inline> $children;

  public function __construct(
    vec<?Inlines\Inline> $children,
  ) {
    $this->children = Vec\filter_nulls($children);
  }

  public static function flatten(?Inlines\Inline ...$children): this {
    return new self(vec($children));
  }

  public function getChildren(): vec<Inlines\Inline> {
    return $this->children;
  }
}
