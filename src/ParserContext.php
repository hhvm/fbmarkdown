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

use type Facebook\Markdown\UnparsedBlocks\Context as BlockContext;
use type Facebook\Markdown\Inlines\Context as InlineContext;

//$parser_context->setSourceType(SourceType::TRUSTED|SourceType::SPONSORED|SourceType::USER_GENERATED_CONTENT)

enum SourceType: int {
  TRUSTED = 0;
  SPONSORED = 1;
  USER_GENERATED_CONTENT = 2;
}

final class ParserContext {
  private BlockContext $blockContext;
  private InlineContext $inlineContext;

  public function __construct() {
    $this->blockContext = new BlockContext();
    $this->inlineContext = new InlineContext();
  }

  public function setSourceType(SourceType $type): this {
    switch ($type) {
      case SourceType::TRUSTED:
        $this->enableAllFeaturesForTrustedInput_UNSAFE();
        break;
      case SourceType::SPONSORED:
        $this->setURISchemeAllowList();
        break;
      case SourceType::USER_GENERATED_CONTENT:
        break;
    }
    return $this;
  }

  public function enableAllFeaturesForTrustedInput_UNSAFE(): this {
    $this->enableHTML_UNSAFE();
    $this->enableAllURISchemes_UNSAFE();
    return $this;
  }

  public function enableHTML_UNSAFE(): this {
    $this->blockContext->enableHTML_UNSAFE();
    $this->inlineContext->enableHTML_UNSAFE();
    return $this;
  }

  public function setURISchemeAllowList(
    keyset<string> $allowlist = keyset["http", "https", "irc", "mailto"],
  ): this {
    $this->blockContext->setURISchemeAllowList($allowlist);
    $this->inlineContext->setURISchemeAllowList($allowlist);
    return $this;
  }

  public function enableAllURISchemes_UNSAFE(): this {
    $this->blockContext->enableAllURISchemes_UNSAFE();
    $this->inlineContext->enableAllURISchemes_UNSAFE();
    return $this;
  }

  public function disableExtensions(): this {
    $this->blockContext->disableExtensions();
    $this->inlineContext->disableExtensions();
    return $this;
  }

  public function disableNamedExtension(string $name): this {
    $this->blockContext->disableNamedExtension($name);
    $this->inlineContext->disableNamedExtension($name);
    return $this;
  }

  public function enableNamedExtension(string $name): this {
    $this->blockContext->enableNamedExtension($name);
    $this->inlineContext->enableNamedExtension($name);
    return $this;
  }

  public function setFilePath(string $file): this {
    $this->blockContext->setFilePath($file);
    return $this;
  }

  public function resetFileData(): this {
    $this->blockContext->resetFileData();
    $this->inlineContext->resetFileData();
    return $this;
  }

  public function getBlockContext(): BlockContext {
    return $this->blockContext;
  }

  public function getInlineContext(): InlineContext {
    return $this->inlineContext;
  }

  public function setBlockContext(BlockContext $ctx): this {
    $this->blockContext = $ctx;
    return $this;
  }

  public function setInlineContext(InlineContext $ctx): this {
    $this->inlineContext = $ctx;
    return $this;
  }
}
