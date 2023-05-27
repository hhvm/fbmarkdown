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

// HHAST_IGNORE_ALL[DontUseAsioJoin]
// We want to offer this renderer to those who can't upgrade to HTMLXHPRenderer.
// HTMLRenderer is a Renderer<string>, so HTMLWithXHPInternallyRenderer is too.
// This forces us to run the Awaitables in a blocking fashion.

use namespace HH\Asio;
use namespace Facebook\XHP;

final class HTMLWithXHPInternallyRenderer extends Renderer<string> {
  private IRenderer<XHP\Core\node> $impl;

  public function __construct(RenderContext $context) {
    parent::__construct($context);
    $this->impl = new HTMLXHPRenderer($context);
  }

  <<__Override>>
  protected function renderNodes(vec<ASTNode> $nodes): string {
    return $this->impl->renderNodes($nodes) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderResolvedNode(ASTNode $node): string {
    return
      $this->impl->renderResolvedNode($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderBlankLine(): string {
    return $this->impl->renderBlankLine() |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderBlockQuote(Blocks\BlockQuote $node): string {
    return
      $this->impl->renderBlockQuote($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderCodeBlock(Blocks\CodeBlock $node): string {
    return
      $this->impl->renderCodeBlock($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderHeading(Blocks\Heading $node): string {
    return $this->impl->renderHeading($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderHTMLBlock(Blocks\HTMLBlock $node): string {
    return
      $this->impl->renderHTMLBlock($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderLinkReferenceDefinition(
    Blocks\LinkReferenceDefinition $def,
  ): string {
    return $this->impl->renderLinkReferenceDefinition($def)
      |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderListOfItems(Blocks\ListOfItems $node): string {
    return
      $this->impl->renderListOfItems($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderParagraph(Blocks\Paragraph $node): string {
    return
      $this->impl->renderParagraph($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderTableExtension(Blocks\TableExtension $node): string {
    return $this->impl->renderTableExtension($node)
      |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderThematicBreak(): string {
    return $this->impl->renderThematicBreak() |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderAutoLink(Inlines\AutoLink $node): string {
    return $this->impl->renderAutoLink($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderInlineWithPlainTextContent(
    Inlines\InlineWithPlainTextContent $node,
  ): string {
    return $this->impl->renderInlineWithPlainTextContent($node)
      |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderCodeSpan(Inlines\CodeSpan $node): string {
    return $this->impl->renderCodeSpan($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderEmphasis(Inlines\Emphasis $node): string {
    return $this->impl->renderEmphasis($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderHardLineBreak(): string {
    return $this->impl->renderHardLineBreak() |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderImage(Inlines\Image $node): string {
    return $this->impl->renderImage($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderLink(Inlines\Link $node): string {
    return $this->impl->renderLink($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderRawHTML(Inlines\RawHTML $node): string {
    return $this->impl->renderRawHTML($node) |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderSoftLineBreak(): string {
    return $this->impl->renderSoftLineBreak() |> Asio\join($$->toStringAsync());
  }

  <<__Override>>
  protected function renderStrikethroughExtension(
    Inlines\StrikethroughExtension $node,
  ): string {
    return $this->impl->renderStrikethroughExtension($node)
      |> Asio\join($$->toStringAsync());
  }
}
