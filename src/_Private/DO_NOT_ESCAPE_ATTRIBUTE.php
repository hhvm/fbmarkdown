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

// HHAST_IGNORE_ERROR[CamelCasedMethodsUnderscoredFunctions] intentionally SHOUT_CASE
function DO_NOT_ESCAPE_ATTRIBUTE<T>(string $danger_danger_danger): T {
  return \HH\FIXME\UNSAFE_CAST<DO_NOT_ESCAPE_ATTRIBUTE, T>(
    new DO_NOT_ESCAPE_ATTRIBUTE($danger_danger_danger),
    'XHP has this feature, where it allows you to set unsafe attributes. '.
    'This api is deprecated and the typechecker does not know about it. '.
    'By casting to a unbound T, the callers get `nothing`, which hides this, '.
    'from the typechecker. Use sparingly!',
  );
}

final class DO_NOT_ESCAPE_ATTRIBUTE
  extends XHP\UnsafeAttributeValue_DEPRECATED {
  public function __construct(private string $dangerDangerDanger) {}

  public function toHTMLString(): string {
    return $this->dangerDangerDanger;
  }
}
