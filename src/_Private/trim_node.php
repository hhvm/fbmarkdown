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

use namespace HH\Lib\{Str, Vec};
use type Facebook\XHP\AlwaysValidChild;
use type Facebook\XHP\Core\primitive;

final xhp class trim_node extends primitive implements AlwaysValidChild {
  public async function stringifyAsync(): Awaitable<string> {
    return await Vec\map_async(
      $this->getChildren(),
      async $c ==> await static::renderChildAsync($c),
    )
      |> Str\join($$, '')
      |> Str\trim($$);
  }
}
