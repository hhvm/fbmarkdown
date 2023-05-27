<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\Inlines;

class SoftLineBreak extends Inline {
  public function __construct() {
  }

  <<__Override>>
  public function getContentAsPlainText(): string {
    return "\n";
  }

  <<__Override>>
  public static function consume(
    Context $_,
    string $string,
    int $offset,
  ): ?(Inline, int) {
    if ($string[$offset] === "\n") {
      return tuple(new self(), $offset + 1);
    }
    return null;
  }
}
