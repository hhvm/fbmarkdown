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

abstract class InlineWithPlainTextContent extends Inline {
  final public function __construct(
    private string $content,
  ) {
  }

  final public function getContent(): string {
    return $this->content;
  }

  <<__Override>>
  final public function getContentAsPlainText(): string {
    return $this->content;
  }
}
