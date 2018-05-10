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

abstract class CodeBlock extends LeafBlock {
  final public function __construct(
    private ?string $infoString,
    private string $code,
  ) {
  }

  final public function getInfoString(): ?string {
    return $this->infoString;
  }

  final public function getCode(): string {
    return $this->code;
  }
}
