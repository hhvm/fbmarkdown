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

use type IDisposable;

<<__ReturnDisposable>>
function disable_child_validation(): IDisposable {
  return new ChildValidationDisablerDisposable();
}
