<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\UnparsedBlocks;

use const Facebook\Markdown\_Private\ASCII_PUNCTUATION;
use function Facebook\Markdown\_Private\decode_html_entity;
use type Facebook\Markdown\Blocks\FencedCodeBlock as ASTNode;
use namespace Facebook\Markdown\Inlines;
use namespace HH\Lib\{C, Regex, Str, Vec};

<<__ConsistentConstruct>>
class FencedCodeBlock extends FencedBlock {

  public function __construct(
    private string $content,
    private ?string $infoString,
  ) {
  }

  public function getContent(): string {
    return $this->content;
  }

  public function getInfoString(): ?string {
    return $this->infoString;
  }

  private static function getPattern(
  ): Regex\Pattern<
    shape('indent' => string, 'fence' => string, 'info' => string, ...),
  > {
    return re"/^(?<indent> {0,3})(?<fence>`{3,}|~{3,})(?<info>[^`]*)?$/";
  }

  <<__Override>>
  protected static function createFromLines(
    vec<string> $lines,
    int $column,
    bool $eof,
  ): this {
    $first = C\firstx($lines);
    $matches = Regex\first_match($first, self::getPattern());
    invariant($matches is nonnull, 'Invalid first line');
    $info = Str\trim($matches['info'] ?? '');
    if ($info === '') {
      $info = null;
    } else {
      $new_info = '';
      $len = Str\length($info);
      for ($i = 0; $i < $len; ++$i) {
        $char = $info[$i];
        if (
          $char === '\\'
          && $i + 1 < $len
        ) {
          $next = $info[$i + 1];
          if (C\contains_key(ASCII_PUNCTUATION, $next)) {
            $new_info .= $next;
            ++$i;
            continue;
          }
        }
        if ($char === '&') {
          $result = decode_html_entity(Str\slice($info, $i));
          if ($result !== null) {
            list($match, $entity, $_rest) = $result;
            $new_info .= $entity;
            $i += Str\length($match) - 1;
            continue;
          }
        }
        $new_info .= $char;
      }
      $info = $new_info;
    }
    $indent = Str\length($matches['indent']);

    $content = $lines
      |> Vec\slice($$, 1, C\count($lines) - ($eof ? 1 : 2))
      |> Vec\map($$, $line ==> self::unindentLine($line, $indent, $column))
      |> Str\join($$, "\n");
    return new static($content, $info);
  }

  private static function unindentLine(
    string $line,
    int $indent,
    int $column,
  ): string {
    if ($indent === 0) {
      return $line;
    }
    $stripped = Lines::stripNLeadingWhitespace($line, $indent, $column);
    if ($stripped !== null) {
      return $stripped;
    }

    return Str\trim_left($line);
  }

  <<__Override>>
  protected static function getEndPatternForFirstLine(
    Context $_,
    int $_column,
    string $first,
  ): ?string {
    $matches = Regex\first_match($first, self::getPattern());
    if ($matches is null) {
      return null;
    }
    return '/^ {0,3}'.$matches['fence'].'+ *$/';
  }

  <<__Override>>
  public function withParsedInlines(Inlines\Context $_): ASTNode {
    return new ASTNode($this->infoString, $this->content);
  }
}
