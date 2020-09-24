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
        return vec[
            // LINKS
            tuple(
                "[a](javascript:prompt(document.cookie))",
                "<p>[a](javascript:prompt(document.cookie))</p>\n",
            ),
            tuple(
                "[notmalicious](javascript:window.onerror=alert;throw%20document.cookie)",
                "<p>[notmalicious](javascript:window.onerror=alert;throw%20document.cookie)</p>\n",
            ),
            tuple(
                "[a](j    a   v   a   s   c   r   i   p   t:prompt(document.cookie))",
                "<p>[a](j    a   v   a   s   c   r   i   p   t:prompt(document.cookie))</p>\n",
            ),
            tuple(
                "[test](javascript://%0d%0aprompt(1))",
                "<p>[test](javascript://%0d%0aprompt(1))</p>\n",
            ),
            tuple(
                "[test](javascript://%0d%0aprompt(1);com)",
                "<p>[test](javascript://%0d%0aprompt(1);com)</p>\n",
            ),
            tuple(
                "[notmalicious](javascript:window.onerror=alert;throw%20document.cookie)",
                "<p>[notmalicious](javascript:window.onerror=alert;throw%20document.cookie)</p>\n",
            ),
            tuple(
                "[notmalicious](javascript://%0d%0awindow.onerror=alert;throw%20document.cookie)",
                "<p>[notmalicious](javascript://%0d%0awindow.onerror=alert;throw%20document.cookie)</p>\n",
            ),
            tuple(
                "[a](data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4K)",
                "<p>[a](data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4K)</p>\n",
            ),
            tuple(
                "[clickme](vbscript:alert(document.domain))",
                "<p>[clickme](vbscript:alert(document.domain))</p>\n",
            ),
            tuple(
                "[a](javascript:this;alert(1))",
                "<p>[a](javascript:this;alert(1))</p>\n",
            ),
            tuple(
                "[a](javascript://www.google.com%0Aprompt(1))",
                "<p>[a](javascript://www.google.com%0Aprompt(1))</p>\n",
            ),
            tuple(
                "[a](javascript://%0d%0aconfirm(1);com)",
                "<p>[a](javascript://%0d%0aconfirm(1);com)</p>\n",
            ),
            tuple(
                "[a](javascript:window.onerror=confirm;throw%201)",
                "<p>[a](javascript:window.onerror=confirm;throw%201)</p>\n",
            ),
            tuple(
                "[a](javascript://www.google.com%0Aalert(1))",
                "<p>[a](javascript://www.google.com%0Aalert(1))</p>\n",
            ),
            tuple(
                "[a]('javascript:alert(\"1\")')",
                "<p>[a]('javascript:alert(&quot;1&quot;)')</p>\n",
            ),
            tuple(
                "[a](JaVaScRiPt:alert(1))",
                "<p>[a](JaVaScRiPt:alert(1))</p>\n",
            ),
            // AUTOLINKS
            tuple(
                "</http://<?php\><\h1\><script:script>confirm(2)",
                "<p>&lt;/http://&lt;?php&gt;&lt;\h1&gt;&lt;script:script&gt;confirm(2)</p>\n",
            ),
            tuple(
                "<javascript:prompt(document.cookie)>",
                "<p>&lt;javascript:prompt(document.cookie)&gt;</p>\n",
            ),
            tuple(
                "<atielking@slack-corp.com>",
                "<p>&lt;atielking@slack-corp.com&gt;</p>\n",
            ),
            // LINK REFERENCE DEFINITIONS
            tuple(
                "[foo]:
javascript:prompt(document.cookie)

[foo]",
                "<p>[foo]:\njavascript:prompt(document.cookie)</p>\n<p>[foo]</p>\n",
            ),
        ];
    }
}
