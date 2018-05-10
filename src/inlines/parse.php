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

use namespace HH\Lib\{C, Str};

function parse(
  Context $context,
  string $markdown,
): vec<Inline> {
  list($parsed, $offset) = _Private\parse_with_blacklist(
    $context,
    $markdown,
    0,
    keyset[],
  );
  $length = Str\length($markdown);
  invariant(
    $offset === $length,
    'TextualContent should have consumed everything. '.
    'Offset: %d; Length: %d; Final class: %s',
    $offset,
    $length,
    \get_class(C\lastx($parsed)),
  );
  return $parsed;
}
