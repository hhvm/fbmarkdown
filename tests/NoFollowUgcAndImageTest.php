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

use type Facebook\HackTest\DataProvider;
use function Facebook\FBExpect\expect;
use namespace HH\Lib\{Str};

final class NoFollowUgcAndImageTest extends TestCase {
    const keyset<string> DEFAULT_URI_SCHEME_ALLOW_LIST = keyset['http', 'https', 'irc', 'mailto'];

  protected function assertXSSExampleMatches(
    string $name,
    string $in,
    string $expected_html,
    ?string $extension,
  ): void {
    $parser_ctx = (new ParserContext())
      ->setSourceType(SourceType::USER_GENERATED_CONTENT)
      ->setAllowedURISchemes(self::DEFAULT_URI_SCHEME_ALLOW_LIST)
      ->disableExtensions();
    $render_ctx = (new RenderContext())
      ->addNoFollowUGCAllLinks()
      ->setSourceType(SourceType::USER_GENERATED_CONTENT);

    if ($extension !== null) {
      $parser_ctx->enableNamedExtension($extension);
      $render_ctx->enableNamedExtension($extension);
    }

    $ast = parse($parser_ctx, $in);
    $actual_html = (new HTMLRenderer($render_ctx))->render($ast);

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

  <<DataProvider('getNoFollowUgcAndImageExamples')>>
  public function testXSSExample(string $in, string $expected_html): void {
    $this->assertXSSExampleMatches(
      'unnamed',
      $in,
      $expected_html,
      /* extension = */ 'table',
    );
  }

  public function getNoFollowUgcAndImageExamples(): vec<(string, string)> {
    return vec[
      // LINKS
      tuple(
        '[facebook](https://www.facebook.com)',
        "<p><a href=\"https://www.facebook.com\" rel=\"nofollow ugc\">facebook</a></p>\n",
      ),
      // AUTOLINKS
      tuple(
        '<https://www.facebook.com>',
        "<p><a href=\"https://www.facebook.com\" rel=\"nofollow ugc\">https://www.facebook.com</a></p>\n",
      ),

      // LINK REFERENCE DEFINITIONS
      tuple(
        '[foo]:
https://www.facebook.com

[foo]',
        "<p><a href=\"https://www.facebook.com\" rel=\"nofollow ugc\">foo</a></p>\n",
      ),
      // IMAGES
      tuple(
        '<img src="img_girl.jpg" alt="Girl in a jacket" width="500" height="600">',
        '<p>&lt;img src=&quot;img_girl.jpg&quot; alt=&quot;Girl in a jacket&quot; width=&quot;500&quot; height=&quot;600&quot;&gt;</p>'.
        "\n",
      ),
    ];
  }
}
