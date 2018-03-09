<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 *
 */

namespace Facebook\Markdown;

use namespace Facebook\TypeAssert;
use namespace HH\Lib\{C, Str, Vec};

use function Facebook\FBExpect\expect;

final class SpecTest extends TestCase {
  const string EXAMPLE_START = "\n```````````````````````````````` example";
  const string EXAMPLE_END = "\n````````````````````````````````";
  // Sanity check - make sure it matches the last one in the spec
  const int EXAMPLE_COUNT = 649;

  public function getSpecExamples(): array<(string, string, string, ?string)> {
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

    $examples = [];

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

  /** @dataProvider getSpecExamples */
  public function testSpecExample(
    string $name,
    string $in,
    string $expected_html,
    ?string $extension,
  ): void {
    $this->assertExampleMatches($name, $in, $expected_html, $extension);
  }

  /** Parse markdown to an AST, re-serialize it to markdown, then re-parse and
   * finally render to HTML, and check that matches.
   *
   * This is basically a test of `MarkdownRenderer`.
   *
   * @dataProvider getSpecExamples
   */
  public function testSpecExampleNormalizesWithoutHTMLChange(
    string $name,
    string $original_md,
    string $expected_html,
    ?string $extension,
  ): void {
    $this->markTestIncomplete('Still a work in progress');
    $parser_ctx = (new ParserContext())
      ->enableHTML_UNSAFE()
      ->disableExtensions();
    $render_ctx = (new RenderContext())
      ->disableExtensions();
    if ($extension !== null) {
      $parser_ctx->enableNamedExtension($extension);
      $render_ctx->enableNamedExtension($extension);
    }
    $ast = parse($parser_ctx, $original_md);
    $normalized_md = (new MarkdownRenderer($render_ctx))->render($ast);
    print("--- ORIGINAL ---\n");
    print($original_md."\n");
    print("--- NORMALIZED ---\n");
    print($normalized_md."\n");
    print("--- AST ---\n");
    \var_dump($ast);
    print("--- END ---\n");

    $this->assertExampleMatches(
      $name,
      $normalized_md,
      $expected_html,
      $extension,
    );
  }
}
