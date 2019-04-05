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

  public static function html(bool $unsafe = false): Environment<string> {
    $parser = new ParserContext();
    $context = new RenderContext();
    $renderer = new HTMLRenderer($context);

    if ($unsafe) {
        $parser->enableHTML_UNSAFE();
    }

    return new self($parser, $context, $renderer);
  }

  public function setParser(ParserContext $parser): void {
    $this->parser = $parser;
  }

  public function getParser(): ParserContext {
    return $this->parser;
  }

  public function setContext(RenderContext $context): void {
    $this->context = $context;
  }

  public function getContext(): RenderContext {
    return $this->context;
  }

  public function setRenderer(Renderer<T> $renderer): void {
    $this->renderer = $renderer;
  }

  public function getRenderer(): Renderer<T> {
    return $this->renderer;
  }

  public function setInlineContext(Inlines\Context $context): void {
    $this->parser->setInlineContext($context);
  }

  public function getInlineContext(): Inlines\Context {
    return $this->parser->getInlineContext();
  }

  public function setBlockContext(UnparsedBlocks\Context $context): void {
    $this->parser->setBlockContext($context);
  }

  public function getBlockContext(): UnparsedBlocks\Context {
    return $this->parser->getBlockContext();
  }

  public function getFilters(): Container<RenderFilter> {
    return $this->context->getFilters();
  }

  public function addFilters(RenderFilter ...$filters): void {
    $this->context->appendFilters(...$filters);
  }

  public function use(Extension $extension): void {
    $this->getBlockContext()
      ->prependBlockTypes(...$extension->getBlockProducers());
    $this->getInlineContext()
      ->prependInlineTypes(...$extension->getInlineTypes());
    $this->addFilters(...$extension->getRenderFilters());
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
