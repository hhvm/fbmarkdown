<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\Inlines;

use type Facebook\Markdown\UnparsedBlocks\Context as BlockContext;
use namespace HH\Lib\{C, Keyset, Str};

class Context {
  const keyset<classname<Inline>> ALL_INLINE_TYPES = keyset[
    HardLineBreak::class,
    SoftLineBreak::class,
    BackslashEscape::class,
    AutoLink::class,
    AutoLinkExtension::class,
    Link::class,
    CodeSpan::class,
    EntityReference::class,
    Image::class,
    RawHTML::class,
    Emphasis::class,
    StrikethroughExtension::class,
    TextualContent::class,
  ];

  protected bool $isHtmlEnabled = false;
  protected bool $areAllURISchemesEnabled = false;
  private keyset<string> $allowedURISchemes = keyset[];

  public function enableAllURISchemes_UNSAFE(): this {
    $this->areAllURISchemesEnabled = true;
    return $this;
  }

  public function setAllowedURISchemes(keyset<string> $allowlist): this {
    $this->allowedURISchemes = $allowlist;
    return $this;
  }

  public function enableHTML_UNSAFE(): this {
    $this->isHtmlEnabled = true;
    return $this;
  }

  public function isHTMLEnabled(): bool {
    return $this->isHtmlEnabled;
  }

  public function areAllURISchemesEnabled(): bool {
    return $this->areAllURISchemesEnabled;
  }

  public function getAllowedURISchemes(): keyset<string> {
    return $this->allowedURISchemes;
  }

  public function getFilePath(): ?string {
    return $this->getBlockContext()->getFilePath();
  }

  private ?BlockContext $blockContext;

  public function setBlockContext(BlockContext $ctx): this {
    $this->blockContext = $ctx;
    return $this;
  }

  public function getBlockContext(): BlockContext {
    $ctx = $this->blockContext;
    invariant(
      $ctx !== null,
      'Call %s::setBlockContext before parsing inlines',
      static::class,
    );
    return $ctx;
  }

  public function resetFileData(): this {
    $this->blockContext = null;
    return $this;
  }

  public function disableExtensions(): this {
    $this->disabledInlineTypes = $this->inlineTypes
      |> Keyset\filter($$, $class ==> Str\ends_with($class, 'Extension'))
      |> Keyset\union($$, $this->disabledInlineTypes);
    return $this;
  }

  public function disableNamedExtension(string $name): this {
    $this->disabledInlineTypes = Keyset\union(
      $this->disabledInlineTypes,
      Keyset\filter(
        self::ALL_INLINE_TYPES,
        $class ==> Str\ends_with(Str\lowercase($class), '\\'.$name.'extension'),
      ),
    );
    return $this;
  }

  public function enableNamedExtension(string $name): this {
    $this->disabledInlineTypes = Keyset\filter(
      $this->disabledInlineTypes,
      $class ==> !Str\ends_with(
        Str\lowercase($class),
        '\\'.Str\lowercase($name).'extension',
      ),
    );
    return $this;
  }

  private keyset<classname<Inline>> $inlineTypes = self::ALL_INLINE_TYPES;
  private keyset<classname<Inline>> $disabledInlineTypes = keyset[];

  public function getInlineTypes(): keyset<classname<Inline>> {
    return Keyset\filter(
      $this->inlineTypes,
      $type ==> !C\contains($this->disabledInlineTypes, $type),
    );
  }

  public function prependInlineTypes(classname<Inline> ...$types): this {
    $this->inlineTypes = Keyset\union($types, $this->inlineTypes);
    return $this;
  }
}
