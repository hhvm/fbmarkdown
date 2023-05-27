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

use namespace Facebook\XHP\ChildValidation;
use type IDisposable;

final class ChildValidationDisablerDisposable implements IDisposable {
  private bool $shouldRestore;

  public function __construct() {
    $this->shouldRestore = ChildValidation\is_enabled();
    ChildValidation\disable();
  }

  public function __dispose(): void {
    if ($this->shouldRestore) {
      ChildValidation\enable();
    }
  }
}
