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
use namespace HH\Asio;

// HHAST_IGNORE_ERROR[CamelCasedMethodsUnderscoredFunctions] intentionally SHOUT_CASE
function FORCE_RENDER(\XHPChild $child): string {
  if ($child is XHP\UnsafeRenderable) {
    // HHAST_FIXME[DontUseAsioJoin] We'll cross that bridge when we get to it.
    return Asio\join($child->toHTMLStringAsync());
  }

  if ($child is XHP\Core\node) {
    // HHAST_FIXME[DontUseAsioJoin] We'll cross that bridge when we get to it.
    return Asio\join($child->toStringAsync());
  }

  if (\is_scalar($child)) {
    // Mostly for me during this migration.
    \error_log((new \Exception('Asked to render scalar at:'))->toString());
    return (string)$child;
  }

  invariant_violation(
    'How to render: %s?',
    \is_object($child) ? \get_class($child) : \gettype($child),
  );
}
