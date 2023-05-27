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

use type Facebook\Markdown\Blocks\Document as Document;

use namespace HH\Lib\{Str, Vec};

class RenderContext {
  private vec<RenderFilter> $extensions;
  private vec<RenderFilter> $enabledExtensions;
  private vec<RenderFilter> $filters = vec[];
  private bool $areLinksNoFollowUGC = false;
  private ?Document $document;

  public function __construct() {
    $this->extensions = vec[
      new TagFilterExtension(),
    ];
    $this->enabledExtensions = $this->extensions;
  }

  public function setSourceType(SourceType $type): this {
    switch ($type) {
      case SourceType::TRUSTED:
        $this->disableImageFiltering();
        break;
      case SourceType::SPONSORED:
        $this->addNoFollowUGCAllLinks();
        break;
      case SourceType::USER_GENERATED_CONTENT:
        $this->addNoFollowUGCAllLinks();
        break;
    }
    return $this;
  }

  public function disableExtensions(): this {
    $this->enabledExtensions = vec[];
    return $this;
  }

  public function addNoFollowUGCAllLinks(): this {
    $this->areLinksNoFollowUGC = true;
    return $this;
  }

  public function areLinksNoFollowUGC(): bool {
    return $this->areLinksNoFollowUGC;
  }

  public function disableNamedExtension(string $extension): this {
    $this->enabledExtensions = Vec\filter(
      $this->enabledExtensions,
      $obj ==> !Str\ends_with_ci(\get_class($obj), '\\'.$extension.'Extension'),
    );
    return $this;
  }

  public function disableImageFiltering(): this {
    foreach ($this->extensions as $extension) {
      if ($extension is TagFilterExtension) {
        $extension->removeFromTagBlacklist(keyset['<img']);
      }
    }
    return $this;
  }

  public function enableNamedExtension(string $extension): this {
    $this->enabledExtensions = $this->extensions
      |> Vec\filter(
        $$,
        $obj ==>
          Str\ends_with_ci(\get_class($obj), '\\'.$extension.'Extension'),
      )
      |> Vec\concat($$, $this->enabledExtensions)
      |> Vec\unique_by($$, $x ==> \get_class($x));
    return $this;
  }

  public function setDocument(Document $document): this {
    invariant(
      $this->document === null,
      'Call %s::resetFileData between files',
      static::class,
    );
    $this->document = $document;
    return $this;
  }

  public function getDocument(): Document {
    $doc = $this->document;
    invariant(
      $doc !== null,
      'call %s::setDocument before attempting to render',
      static::class,
    );
    return $doc;
  }

  public function resetFileData(): this {
    foreach ($this->getFilters() as $filter) {
      $filter->resetFileData();
    }
    $this->document = null;
    return $this;
  }

  public function getFilters(): vec<RenderFilter> {
    return Vec\concat($this->filters, $this->enabledExtensions);
  }

  public function appendFilters(RenderFilter ...$filters): this {
    $this->filters = Vec\concat($this->filters, $filters);
    return $this;
  }

  public function transformNode(ASTNode $node): vec<ASTNode> {
    $nodes = vec[$node];
    foreach ($this->getFilters() as $filter) {
      $nodes = $nodes
        |> Vec\map($$, $node ==> $filter->filter($this, $node))
        |> Vec\flatten($$);
    }
    return $nodes;
  }
}
