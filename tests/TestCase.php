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

use namespace HH\Lib\Str;
use function Facebook\FBExpect\expect;
use namespace Facebook\XHP;
use type XHPChild;

abstract class TestCase extends \Facebook\HackTest\HackTest {
  const string TAB_REPLACEMENT = "\u{2192}";
  const dict<string, string> BOOLEAN_ATTRIBUTE_REPLACEMENTS = dict[
    ' checked ' => ' checked="" ',
    ' disabled ' => ' disabled="" ',
  ];

  public function provideHTMLRendererConstructors(
  )[]: vec<((function(RenderContext): IRenderer<XHPChild>))> {
    return vec[
      tuple(($ctx)[defaults] ==> new HTMLRenderer($ctx)),
    ];
  }

  /**
   * UNSAFE UNSAFE UNSAFE UNSAFE UNSAFE UNSAFE UNSAFE UNSAFE UNSAFE UNSAFE
   * Strings are not escaped.
   * They are trusted, because of HTMLRenderer.
   */
  protected static async function unsafeStringifyXHPChildAsync(
    XHPChild $xhp_child,
  ): Awaitable<string> {
    if ($xhp_child is string) {
      return $xhp_child;
    }

    if ($xhp_child is XHP\Core\node) {
      return await $xhp_child->toStringAsync();
    }

    if ($xhp_child is XHP\UnsafeRenderable) {
      return await $xhp_child->toHTMLStringAsync();
    }

    invariant_violation(
      'Extend %s to handle type: %s',
      __METHOD__,
      \is_object($xhp_child) ? \get_class($xhp_child) : \gettype($xhp_child),
    );
  }

  final protected async function assertExampleMatchesAsync(
    string $name,
    string $in,
    string $expected_html,
    ?string $extension,
  ): Awaitable<void> {
    $parser_ctx = (new ParserContext())
      ->setSourceType(SourceType::TRUSTED)
      ->disableExtensions();
    $render_ctx = (new RenderContext())
      ->disableExtensions();
    if ($extension !== null) {
      $parser_ctx->enableNamedExtension($extension);
      $render_ctx->enableNamedExtension($extension);
    }

    $ast = parse($parser_ctx, $in);
    foreach ($this->provideHTMLRendererConstructors() as list($constructor)) {
      $actual_html = await static::unsafeStringifyXHPChildAsync(
        $constructor($render_ctx)->render($ast),
      )
        |> self::addEmptyStringsForBooleanAttributes($$);

      // Improve output readability
      $actual_html = Str\replace($actual_html, "\t", self::TAB_REPLACEMENT);
      $expected_html = Str\replace($expected_html, "\t", self::TAB_REPLACEMENT);

      expect($actual_html)->toBeSame(
        $expected_html,
        "HTML differs for %s:\n%s",
        $name,
        $in,
      );
    }
  }

  private static function addEmptyStringsForBooleanAttributes(
    string $html,
  )[]: string {
    return Str\replace_every($html, static::BOOLEAN_ATTRIBUTE_REPLACEMENTS);
  }
}
