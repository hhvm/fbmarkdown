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

use namespace HH\Lib\{C, Str};

use type Facebook\HackTest\DataProvider;
use function Facebook\FBExpect\expect;

final class SpecTest extends TestCase {
  const string EXAMPLE_START = "\n```````````````````````````````` example";
  const string EXAMPLE_END = "\n````````````````````````````````";
  // Sanity check - make sure it matches the last one in the spec
  const int EXAMPLE_COUNT = 649;

  public function getSpecExamples(): vec<(string, string, string, ?string)> {
    $text = \file_get_contents(__DIR__.'/../third-party/spec.txt');
    $raw_examples = vec[];
    $offset = 0;
    while (true) {
      $start = Str\search($text, self::EXAMPLE_START, $offset);
      if ($start === null) {
        break;
      }
      $start += Str\length(self::EXAMPLE_START);
      $newline = Str\search($text, "\n", $start);
      invariant($newline !== null, "No newline after start marker");
      $extension = Str\trim(Str\slice($text, $start, $newline - $start));
      $start = $newline;
      $end = Str\search($text, self::EXAMPLE_END, $start);
      invariant($end !== null, 'Found start without end at %d', $offset);

      $raw_examples[] = tuple(
        Str\slice($text, $start + 1, ($end - $start)),
        $extension === '' ? null : $extension,
      );
      $offset = $end + Str\length(self::EXAMPLE_END);
    }

    $examples = vec[];

    foreach ($raw_examples as list($example, $extension)) {
      $parts = Str\split($example, "\n.\n");
      $count = C\count($parts);
      invariant(
        $count === 1 || $count === 2,
        "examples should have input and output, example %d has %d parts",
        C\count($examples) + 1,
        $count,
      );
      $examples[] = tuple(
        'Example '.(C\count($examples) + 1),
        Str\replace($parts[0], self::TAB_REPLACEMENT, "\t"),
        $parts[1] ?? '',
        $extension,
      );
    }
    expect(C\count($examples))->toBeSame(
      self::EXAMPLE_COUNT,
      "Did not get the expected number of examples",
    );
    return $examples;
  }

  <<DataProvider('getSpecExamples')>>
  public function testSpecExample(
    string $name,
    string $in,
    string $expected_html,
    ?string $extension,
  ): void {
    $this->assertExampleMatches($name, $in, $expected_html, $extension);
  }
  <<DataProvider('getSpecExamples')>>
  /** Parse markdown to an AST, re-serialize it to markdown, then re-parse and
   * finally render to HTML, and check that matches.
   *
   * This is basically a test of `MarkdownRenderer`.
   *
   */
  public function testSpecExampleNormalizesWithoutHTMLChange(
    string $name,
    string $original_md,
    string $expected_html,
    ?string $extension,
  ): void {
    $parser_ctx = (new ParserContext())
      ->enableTrustedInput_UNSAFE()
      ->disableExtensions();
    $render_ctx = (new RenderContext())
      ->disableExtensions();
    if ($extension !== null) {
      $parser_ctx->enableNamedExtension($extension);
      $render_ctx->enableNamedExtension($extension);
    }

    $ast = parse($parser_ctx, $original_md);
    $normalized_md = (new MarkdownRenderer($render_ctx))->render($ast);
    $normalized_ast = parse($parser_ctx, $normalized_md);
    $actual_html = (new HTMLRenderer($render_ctx))->render($normalized_ast);

    $actual_html = self::normalizeHTML($actual_html);
    $expected_html = self::normalizeHTML($expected_html);

    expect($actual_html)->toBeSame(
      $expected_html,
      "HTML differs for %s:\n%s",
      $name,
      $original_md,
    );
  }

  private static function normalizeHTML(string $html): string {
    if ($html === '') {
      return '';
    }
    $html = Str\replace($html, "\t", self::TAB_REPLACEMENT);
    $old = \libxml_use_internal_errors(true);
    try {
      $doc = new \DOMDocument();
      $doc->loadHTML(
        $html,
        \LIBXML_NOENT | \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD,
      );
      return $doc->saveHTML();
    } finally {
      \libxml_use_internal_errors($old);
    }
  }
}
