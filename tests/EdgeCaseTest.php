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

final class EdgeCaseTest extends TestCase {
  public function getManualExamples(): vec<(string, string)> {
    return vec[
      tuple("- foo\n\n", "<ul>\n<li>foo</li>\n</ul>\n"),
      tuple("- foo\n\n\n", "<ul>\n<li>foo</li>\n</ul>\n"),
      tuple(
        "foo|bar\n---|---\n`\|\|`|herp\n",
        "<table>\n<thead>\n".
        "<tr>\n<th>foo</th>\n<th>bar</th>\n</tr>\n".
        "</thead>\n<tbody>\n".
        "<tr>\n<td><code>||</code></td>\n<td>herp</td>\n</tr>".
        "</tbody></table>\n",
      ),
      // Already covered in the spec, but emphasizing here as they
      // illustrate correct binding for the next problme
      tuple('**foo*', "<p>*<em>foo</em></p>\n"),
      tuple('*foo**', "<p><em>foo</em>*</p>\n"),
      // Uncovered a bug
      tuple(
        '*foo **bar *baz bim** bam*',
        "<p>*foo <em><em>bar <em>baz bim</em></em> bam</em></p>\n",
      ),
      tuple(
        '*foo __bar *baz bim__ bam*',
        "<p><em>foo <strong>bar *baz bim</strong> bam</em></p>\n",
      ),
      // CR
      tuple(
        "CR Foo\x0d\x0d> Bar\x0d\x0dBaz",
        "<p>CR Foo</p>\n<blockquote>\n<p>Bar</p>\n</blockquote>\n<p>Baz</p>\n",
      ),
      // LF
      tuple(
        "LF Foo\x0a\x0a> Bar\x0a\x0aBaz",
        "<p>LF Foo</p>\n<blockquote>\n<p>Bar</p>\n</blockquote>\n<p>Baz</p>\n",
      ),
      // CRLF
      tuple(
        "CRLF Foo\x0d\x0a\x0d\x0a> Bar\x0d\x0a\x0d\x0aBaz",
        "<p>CRLF Foo</p>\n<blockquote>\n<p>Bar</p>\n</blockquote>\n<p>Baz</p>\n",
      ),
    ];
  }

  <<DataProvider('getManualExamples')>>
  public function testManualExample(string $in, string $expected_html): void {
    $this->assertExampleMatches(
      'unnamed',
      $in,
      $expected_html,
      /* extension = */ 'table',
    );
  }

  public function testTagFilter(): void {
    $ast = parse(
      (new ParserContext())->enableTrustedInput_UNSAFE(),
      '<iframe />',
    );
    $html = (new HTMLRenderer(new RenderContext()))->render($ast);
    expect($html)->toBeSame("&lt;iframe />\n");
    $html = (
      new HTMLRenderer(
        (new RenderContext())->disableNamedExtension('TagFilter'),
      )
    )->render($ast);
    expect($html)->toBeSame("<iframe />\n");
  }
}
