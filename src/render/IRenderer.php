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

interface IRenderer<+T> {
  protected function getContext(): RenderContext;

  public function render(ASTNode $node): T;

  protected function renderNodes(vec<ASTNode> $nodes): T;

  ///// blocks /////

  protected function renderBlankLine(): T;
  protected function renderBlockQuote(Blocks\BlockQuote $node): T;
  protected function renderCodeBlock(Blocks\CodeBlock $node): T;
  protected function renderDocument(Blocks\Document $node): T;
  protected function renderHeading(Blocks\Heading $node): T;
  protected function renderHTMLBlock(Blocks\HTMLBlock $node): T;
  protected function renderLinkReferenceDefinition(
    Blocks\LinkReferenceDefinition $node,
  ): T;
  protected function renderListOfItems(Blocks\ListOfItems $node): T;
  protected function renderParagraph(Blocks\Paragraph $node): T;
  protected function renderTableExtension(Blocks\TableExtension $node): T;
  protected function renderThematicBreak(): T;

  ///// inlines ////

  protected function renderAutoLink(Inlines\AutoLink $node): T;
  protected function renderCodeSpan(Inlines\CodeSpan $node): T;
  protected function renderEmphasis(Inlines\Emphasis $node): T;
  protected function renderHardLineBreak(): T;
  protected function renderImage(Inlines\Image $node): T;
  protected function renderInlineWithPlainTextContent(
    Inlines\InlineWithPlainTextContent $node,
  ): T;
  protected function renderLink(Inlines\Link $node): T;
  protected function renderRawHTML(Inlines\RawHTML $node): T;
  protected function renderSoftLineBreak(): T;
  protected function renderStrikethroughExtension(
    Inlines\StrikethroughExtension $node,
  ): T;

  protected function renderResolvedNode(ASTNode $node): T;
}
