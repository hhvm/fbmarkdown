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

use namespace Facebook\XHP\ChildValidation as XHPChild;
use namespace Facebook\XHP\HTML\Category;
use type Facebook\XHP\HTML\element;

final xhp class th_with_align extends element implements Category\Sectioning {
  use XHPChild\Validation;
  attribute :Facebook:XHP:HTML:th,
    string align;

  protected static function getChildrenDeclaration(): XHPChild\Constraint {
    return XHPChild\any_number_of(
      XHPChild\any_of(XHPChild\pcdata(), XHPChild\of_type<Category\Flow>()),
    );
  }

  protected string $tagName = 'th';
}
