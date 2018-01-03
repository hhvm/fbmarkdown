<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\Markdown\_Private;

use namespace HH\Lib\Dict;

<<__Memoize>>
function get_html_entity_table(): dict<string, string> {
  $file = __DIR__.'/../../third-party/entities.json';
  invariant(
    \file_exists($file),
    "Expected %s to exist",
    $file,
  );
  $data = \json_decode(\file_get_contents($file), JSON_FB_HACK_ARRAYS);

  return Dict\map($data, $x ==> $x['characters']);
}
