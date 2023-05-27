<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\_Private;

use namespace Facebook\XHP;

final class EscapedAttribute extends XHP\UnsafeAttributeValue_DEPRECATED {
  public function __construct(private string $dangerDangerDanger) {}

  <<__Override>>
  public function toHTMLString(): string {
    return $this->dangerDangerDanger;
  }
}
