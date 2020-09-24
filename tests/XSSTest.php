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

final class XSSTest extends TestCase {
    protected function assertXSSExampleMatches(
        string $name,
        string $in,
        string $expected_html,
        ?string $extension,
    ): void {
        $parser_ctx = (new ParserContext())
            ->enableHTML_UNSAFE()
            ->disableExtensions();
        $render_ctx = (new RenderContext())
            ->disableExtensions();
        if ($extension !== null) {
            $parser_ctx->enableNamedExtension($extension);
            $render_ctx->enableNamedExtension($extension);
        }

        $ast = parse($parser_ctx, $in);
        $actual_html = (new HTMLRenderer($render_ctx))->render($ast);

        // Improve output readability
        $actual_html = Str\replace($actual_html, "\t", self::TAB_REPLACEMENT);
        $expected_html = Str\replace(
            $expected_html,
            "\t",
            self::TAB_REPLACEMENT,
        );

        expect($actual_html)->toBeSame(
            $expected_html,
            "HTML differs for %s:\n%s",
            $name,
            $in,
        );
    }

    <<DataProvider('getXSSExamples')>>
    public function testXSSExample(string $in, string $expected_html): void {
        $this->assertXSSExampleMatches(
            'unnamed',
            $in,
            $expected_html,
            /* extension = */ 'table',
        );
    }

    public function getXSSExamples(): vec<(string, string)> {
        return vec[tuple(
            "[a](javascript:prompt(document.cookie))",
            "<p>javascript:prompt(document.cookie)</p>",
        )];
    }
}
