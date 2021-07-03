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

abstract class Plugin {
  /**
   * @see Facebook\Markdown\RenderContext::appendFilters()
   */
  public function getRenderFilters(): Container<RenderFilter> {
    return vec[];
  }

  /**
   * @see Facebook\Markdown\Inlines\Context::prependInlineTypes()
   */
  public function getInlineTypes(): Container<classname<Inlines\Inline>> {
    return vec[];
  }

  /**
   * @see Facebook\Markdown\UnparsedBlocks\Context::prependBlockTypes()
   */
  public function getBlockProducers(
  ): Container<classname<UnparsedBlocks\BlockProducer>> {
    return vec[];
  }
}
