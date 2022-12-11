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

final class Environment<T> {
  public function __construct(
    private ParserContext $parser,
    private RenderContext $context,
    private Renderer<T> $renderer,
  ) {}

  public function getParser(): ParserContext {
    return $this->parser;
  }

  public function getContext(): RenderContext {
    return $this->context;
  }

  public function getRenderer(): Renderer<T> {
    return $this->renderer;
  }

  public function getInlineContext(): Inlines\Context {
    return $this->parser->getInlineContext();
  }

  public function getBlockContext(): UnparsedBlocks\Context {
    return $this->parser->getBlockContext();
  }

  public function getFilters(): Container<RenderFilter> {
    return $this->context->getFilters();
  }

  public function use(Plugin $plugin): void {
    $this->getBlockContext()
      ->prependBlockTypes(...$plugin->getBlockProducers());
    $this->getInlineContext()
      ->prependInlineTypes(...$plugin->getInlineTypes());
    $this->getContext()->appendFilters(...$plugin->getRenderFilters());
  }

  public function parse(string $markdown): ASTNode {
    return parse($this->parser, $markdown);
  }

  public function render(ASTNode $markdown): T {
    return $this->renderer->render($markdown);
  }

  public function convert(string $markdown): T {
    return $this->render($this->parse($markdown));
  }
}
