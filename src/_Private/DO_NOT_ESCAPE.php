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
use type Facebook\XHP\Core\frag;

/**
 * @see https://codebeforethehorse.tumblr.com/post/87306947716/converting-a-project-to-xhp
 */
// HHAST_IGNORE_ERROR[CamelCasedMethodsUnderscoredFunctions] intentionally SHOUT_CASE
function DO_NOT_ESCAPE(string $danger_danger_danger): frag {
  return <frag>{new DO_NOT_ESCAPE($danger_danger_danger)}</frag>;
}

final class DO_NOT_ESCAPE implements XHP\UnsafeRenderable {
  public function __construct(private string $dangerDangerDanger) {}

  public async function toHTMLStringAsync(): Awaitable<string> {
    return $this->dangerDangerDanger;
  }
}
