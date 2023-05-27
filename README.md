# FBMarkdown
FBMarkdown is an extensible parser and renderer for [GitHub Flavored Markdown](https://github.github.com/gfm/),
written in [Hack](http://hacklang.org).

It is used to render [the Hack and HHVM documentation](https://docs.hhvm.com).

## Why another Markdown library?

For docs.hhvm.com, we wanted:

- GitHub Flavored Markdown for familiarity
- support for custom extensions

Originally, the Ruby GFM pipeline was the best fit; over time, we started to want to:

- make it easier and faster to contribute to docs.hhvm.com
- remove the Ruby dependencies to make it easy to render markdown in other Hack projects
- produce and mutate an AST before rendering
- support multiple renders

FBMarkdown exists to address all of these goals.

## Requirements

- HHVM 3.24 or above.
- [hhvm-autoload](https://github.com/hhvm/hhvm-autoload)

## Installing FBMarkdown

    hhvm composer.phar require facebook/fbmarkdown

## Using FBMarkdown

```Hack
use namespace Facebook\Markdown;

function render(string $markdown): string {
  $ast = Markdown\parse(new Markdown\ParserContext(), $markdown);

  $html = (new Markdown\HTMLRenderer(
    new Markdown\RenderContext()
  ))->render($ast);

  return $html;
}
```

FBMarkdown currently supports three types of Markdown sources, with plans to expand: trusted, sponsored, and user-generated content.

- __Trusted content mode__: Embedded HTML is enabled, and all URI schemes are enabled and will be parsed as links. In addition, all images are processed normally. 

- __Sponsored mode__: HTML rendering is enabled, but limited to allowed tags only (defined in `TagFilterExtension`, based on the GFM spec). Additionally, URIs are limited to the {`http`, `https`, `irc`, and `mailto`} schemes, and `rel="nofollow ugc"` is added to all links.

- __User-generated content__: All HTML is disabled, as are links and images regardless of schemes. If links are re-enabled, `rel="nofollow ugc"` will be added to all links.

To make changes to these default settings:
- You may alter the keyset of allowed URI schemes by calling the Parser function `setAllowedURISchemes()`.
- You may enable embedded HTML by calling the Parser function `enableHTML_UNSAFE()`. __N.B.: For complete compatibility with GitHub Flavored Markdown, support for embedded HTML must be enabled.__ 
- You may disable image filtering by calling the Renderer function `disableImageFiltering()`.
- You may add `rel="nofollow ugc"` to all links by calling the Renderer function `addNoFollowUGCAllLinks()`.

If you are re-using contexts to render multiple independent snippets, you will need to call `->resetFileData()` on the context.

## How FBMarkdown works

### Parsing

1. The classes in the `Facebook\Markdown\UnparsedBlocks` namespace convert
   markdown text to a tree of nodes representing the block structure of
   the document, however the content of the blocks is unparsed.
1. The contents of the blocks ('inlines') are parsed using the classes in the
   `Facebook\Markdown\Inlines` namespace.
1. Finally, the classes of the `Facebook\Markdown\Blocks` namespace are used to
   represent the fully parsed AST - blocks and Inlines.

### Rendering

The AST is recursively walked, emitting output for each note - e.g. the HTML renderer produces strings.

## Extending FBMarkdown

There are 2 main ways to extend FBMarkdown: extending the parser, and transforming the AST.

### Extending The Parser

#### Inlines

Extend `Facebook\Markdown\Inlines\Inline` or a subclass, and pass your classname to
`$render_ctx->getInlineContext()->prependInlineTypes(...)`.

There are then several approaches to rendering:
 - instantiate your subclass, and add support for it to a custom renderer
 - instantiate your subclass, and make it implement the `Facebook\Markdown\RenderableAsXHP` interface
   - Failing that, try the `Facebook\Markdown\RenderableAsHTML` interface.
 - if it could be replaced with several existing inlines, return a
   `Facebook\Markdown\Inlines\InlineSequence`, then you won't need to extend the renderer.

#### Blocks

You will need to implement the `Facebook\Markdown\UnparsedBlocks\BlockProducer` interface, and pass your classname
to `$render_ctx->getBlockContext()->prependBlockTypes(...)`.

There are then several approaches to rendering:
 - create a subclass of `Block`, and add support for it to a custom renderer
 - create a subclass of `Block`, and make it implement the `Facebook\Markdown\RenderableAsXHP` interface
   - Failing that, try the `Facebook\Markdown\RenderableAsHTML` interface.
 - if it could be replaced with several existing blocks, return a
   `Facebook\Markdown\Blocks\BlockSequence`
 - if it could be replaced with a paragraph of inlines, return a `Facebook\Markdown\Blocks\InlineSequenceBlock`

### Transforming The AST

Extend `Facebook\Markdown\RenderFilter`, and pass it to `$render_ctx->appendFilters(...)`.

### Examples

The Hack and HHVM documentation uses most of these approaches; see:

- [context setup](https://github.com/hhvm/user-documentation/blob/master/src/build/MarkdownRenderer.php)
- [implementations](https://github.com/hhvm/user-documentation/tree/master/src/markdown-extensions)

## License

FBMarkdown is MIT-licensed.

FBMarkdown may contain third-party software; see [third\_party\_notices.txt](third_party_notices.txt) for details.
