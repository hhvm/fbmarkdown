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
use type XHPChild;

abstract class TestCase extends \Facebook\HackTest\HackTest {
  const string TAB_REPLACEMENT = "\u{2192}";
  const dict<string, string> BOOLEAN_ATTRIBUTE_REPLACEMENTS = dict[
    ' checked ' => ' checked="" ',
    ' disabled ' => ' disabled="" ',
  ];

  protected function providerHTMLRendererConstructors()[]: vec<(function(
    RenderContext,
  ): IRenderer<XHPChild>)> {
    return vec[
      ($ctx)[defaults] ==> new HTMLRenderer($ctx),
    ];
  }

  final protected function assertExampleMatches(
    string $name,
    string $in,
    string $expected_html,
    ?string $extension,
  ): void {
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
    $actual_html = (new HTMLRenderer($render_ctx))->render($ast)
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

  private static function addEmptyStringsForBooleanAttributes(
    string $html,
  )[]: string {
    return Str\replace_every($html, static::BOOLEAN_ATTRIBUTE_REPLACEMENTS);
  }
}
