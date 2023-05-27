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
use namespace HH\Lib\C;
use type Facebook\XHP\Core\frag;
use type XHPChild;

/**
 * @param $sep is a function, because XHP\Core\node instances should only be
 *        rendered once. This function can return a new instance on each iteration.
 *        For joining with strings, this is irrelevant.
 */
function xhp_join(
  vec<XHPChild> $nodes,
  ?(function(): XHPChild) $sep = null,
): XHP\Core\node {
  if ($sep is null) {
    return <frag>{$nodes}</frag>;
  }

  $out = <frag />;
  $last = C\count($nodes) - 1;

  foreach ($nodes as $i => $node) {
    $out->appendChild($node);

    if ($last !== $i) {
      $out->appendChild($sep());
    }
  }

  return $out;
}
