<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown;

/**
 * @see RenderableAsXHP and use it if you can.
 */
interface RenderableAsHTML {
  public function renderAsHTML(
    RenderContext $context,
    HTMLRenderer $renderer,
  ): string;
}
