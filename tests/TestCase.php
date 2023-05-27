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

use namespace HH\Lib\{Regex, Str};
use function Facebook\FBExpect\expect;
use namespace Facebook\XHP;
use type XHPChild;

abstract class TestCase extends \Facebook\HackTest\HackTest {
  const string TAB_REPLACEMENT = "\u{2192}";

  // Facebook\XHP has some behaviors that are not compatible with the specs.
  // Boolean attributes with the `true` value are rendered as `attr`, but the
  // specs contain `attr=""` everywhere. In HTML4.01 and HTML5, `attr` is valid.
  //
  // Void elements ought to be rendered without a trailing solidus in their opening tag.
  // `<br />` is "corrected" to `<br>` anyhow.
  // XHP does the HTML5 spec compliant thing and doesn't include the trailing solidus.
  const dict<string, string> SLIGHT_DEVIATIONS_FROM_SPEC = dict[
    ' checked ' => ' checked="" ',
    ' disabled ' => ' disabled="" ',
    "<br>\n" => "<br />\n",
    "<hr>\n" => "<hr />\n",
  ];

  public function provideHTMLRendererConstructors(
  )[]: vec<((function(RenderContext): IRenderer<XHPChild>))> {
    return vec[
      tuple(($ctx)[defaults] ==> new HTMLRenderer($ctx)),
      tuple(($ctx)[defaults] ==> new HTMLXHPRenderer($ctx)),
      tuple(($ctx)[defaults] ==> new HTMLWithXHPInternallyRenderer($ctx)),
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
      using (_Private\disable_child_validation()) {
        $actual_html = await static::unsafeStringifyXHPChildAsync(
          $constructor($render_ctx)->render($ast),
        )
          |> self::correctForSpecDeviations($$);
      }
      // Improve output readability
      $actual_html = Str\replace($actual_html, "\t", self::TAB_REPLACEMENT);
      $normalized_expected_html =
        Str\replace($expected_html, "\t", self::TAB_REPLACEMENT);

      expect($actual_html)->toBeSame(
        $normalized_expected_html,
        "HTML differs for %s:\n%s",
        $name,
        $in,
      );
    }
  }

  private static function correctForSpecDeviations(string $html)[]: string {
    return Str\replace_every($html, static::SLIGHT_DEVIATIONS_FROM_SPEC)
      |> self::placeASolidusBeforeTheEndOfTheClosingImgTags($$);
  }

  private static function placeASolidusBeforeTheEndOfTheClosingImgTags(
    string $html,
  )[]: string {
    // https://stackoverflow.com/posts/1732454/revisions
    // #pragma enable module(guard(superstition))
    return Regex\replace_with($html, re'#<img .+?>#', $he_comes ==> {
      $the_pony = $he_comes[0];

      if ($the_pony[Str\length($the_pony) - 2] === '/') {
        return $the_pony;
      }

      $the_pony[Str\length($the_pony) - 1] = ' ';
      $the_pony .= '/>';
      return $the_pony;
    });
    // #endpragma
  }
}
