<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\Inlines;

use namespace Facebook\Markdown\Inlines\_Private\EmphasisStack as Stack;
use namespace HH\Lib\{C, Str, Vec};

class Emphasis extends Inline {
  const string UNICODE_WHITESPACE = "[\\pZ\u{0009}\u{000d}\u{000a}\u{000c}]";
  const keyset<string> PUNCTUATION = keyset[
    '!', '"', '#', '$', '%', '&', "'", '(', ')', '*', '+', ',', '-', '.',
    '/', ':', ';', '<', '=', '>', '?', '@', '[', '\\', ']', '^', '_', '`',
    '{', '|', '}', '~',
  ];

  const int IS_START = 1 << 0;
  const int IS_END = 1 << 1;

  public function __construct(
    private bool $isStrong,
    private vec<Inline> $content,
  ) {
  }

  public function isStrong(): bool {
    return $this->isStrong;
  }

  public function getContent(): vec<Inline> {
    return $this->content;
  }

  <<__Override>>
  public function getContentAsPlainText(): string {
    return $this->content
      |> Vec\map($$, $child ==> $child->getContentAsPlainText())
      |> Str\join($$, '');
  }

  <<__Override>>
  public static function consume(
    Context $context,
    string $markdown,
    int $offset,
  ): ?(Inline, int) {
    // Get DelimiterNodes, TextNodes, etc
    $stack = self::tokenize($context, $markdown, $offset);
    if ($stack === null) {
      return null;
    }

    // Create `EmphasisNode`s, remove or shorten some `DelimiterNode`s at the boundaries
    $stack = self::processEmphasis($context, $stack);

    // Stack is fully processed - just need to convert it from stack nodes to AST nodes
    return $stack
      |> Vec\map($$, $node ==> $node->toInlines($context))
      |> Vec\flatten($$)
      |> tuple(new InlineSequence($$), C\lastx($stack)->getEndOffset());
  }

  /**
   * Create a stack of `TextNode`, `InlineNode`, and `DelimiterNode`s.
   *
   * There will not yet be any `EmphasisNode`s - those will be created by `processEmphasis()`.
   */
  private static function tokenize(
     Context $context,
     string $markdown,
     int $initial_offset,
  ): ?vec<Stack\Node> {
    $offset = $initial_offset;
    if (!self::isStartOfRun($context, $markdown, $offset)) {
      return null;
    }
    $first = $markdown[$offset];

    // This is tricky as until we find the closing marker, we don't know if
    // `***` means:
    //  - `<strong><em>`
    //  - `<em><strong>`
    //  - `<em>**`
    //  - `<strong>*`
    //  - `***`

    $start = self::consumeDelimiterRun($markdown, $offset);
    list($start, $end_offset) = $start;
    if (!self::isLeftFlankingDelimiterRun($markdown, $offset, $end_offset)) {
      return null;
    }
    $stack = vec[
      new Stack\DelimiterNode(
        $start,
        self::IS_START,
        $offset,
        $end_offset,
      ),
    ];
    $offset = $end_offset;

    $text = '';

    // Tokenize into a stack of TextNodes, InlineNodes, and DelimiterNodes
    $len = Str\length($markdown);
    for (; $offset < $len; ++$offset) {
      $inline = self::consumeHigherPrecedence($context, $markdown, $offset);
      if ($inline !== null) {
        list($inline, $end_offset) = $inline;
        if ($text !== '') {
          // Leading plain text - *not* what we just matched
          $leading_end = $offset;
          $leading_start = $leading_end - Str\length($text);
          $stack[] = new Stack\TextNode($text, $leading_start, $leading_end);
          $text = '';
        }
        $stack[] = new Stack\InlineNode($inline, $offset, $end_offset);
        $offset = $end_offset - 1;
        continue;
      }

      if (self::isStartOfRun($context, $markdown, $offset)) {
        list($run, $end_offset) = self::consumeDelimiterRun($markdown, $offset);
        $flags = 0;

        if (self::isLeftFlankingDelimiterRun($markdown, $offset, $end_offset)) {
          $flags |= self::IS_START;
        }
        if (
          self::isRightFlankingDelimiterRun($markdown, $offset, $end_offset)
        ) {
          $flags |= self::IS_END;
        }

        if ($flags !== 0) {
          if ($text !== '') {
            // Leading plain text again, before the delimiter
            $leading_end = $offset;
            $leading_start = $leading_end - Str\length($text);
            $stack[] = new Stack\TextNode($text, $leading_start, $leading_end);
            $text = '';
          }
          $stack[] =
            new Stack\DelimiterNode($run, $flags, $offset, $end_offset);
          $offset = $end_offset - 1;
          continue;
        }
      }

      $text .= $markdown[$offset];
    }

    if ($text !== '') {
      $end_offset = $offset;
      $start_offset = $end_offset - Str\length($text);
      $stack[] = new Stack\TextNode($text, $start_offset, $end_offset);
    }

    return $stack;
  }

  /**
   * Take a stack of `TextNode`, `InlineNode`, and `DelimiterNode`, and potentially create
   * `EmphasisNode` replacing some of them (or their content).
   *
   * This is derived from `process_emphasis` in the appendix of the Github-Flavored-Markdown specification.
   */
  private static function processEmphasis(
    Context $context,
    vec<Stack\Node> $stack,
  ): vec<Stack\Node> {
    $position = 0;
    $openers_bottom = dict[
      '*' => vec[0, 0, 0], // indexed by number of chars in delimiter - [0] is unused, but convenient
      '_' => vec[0, 0, 0],
    ];

    while ($position < C\count($stack)) {
      $closer_idx = self::findCloser($stack, $position);
      if ($closer_idx === null) {
        break;
      }
      $position = $closer_idx;
      $closer = $stack[$closer_idx];
      invariant(
        $closer is Stack\DelimiterNode,
        'closer must be a delimiter',
      );
      list($closer_text, $closer_flags) = tuple(
        $closer->getText(),
        $closer->getFlags(),
      );
      $char = $closer_text[0];
      $opener = null;
      $closer_len = Str\length($closer->getText()) % 3;
      $bottom = $openers_bottom[$char][$closer_len];
      for ($i = $position - 1; $i >= $bottom; $i--) {
        $item = $stack[$i];
        if (!$item is Stack\DelimiterNode) {
          continue;
        }
        if (!($item->getFlags() & self::IS_START)) {
          continue;
        }
        if ($item->getText()[0] !== $char) {
          continue;
        }

        // intra-word delimiters must match exactly
        // e.g.
        //  - `*foo**bar` is not emphasized
        //  - `**foo**bar` is emphasized
        if (
          (
            ($closer->getFlags() & self::IS_START)
            || ($item->getFlags() & self::IS_END)
          )
          && (
            (Str\length($closer->getText()) + Str\length($item->getText()))
            % 3 === 0
          )
        ) {
          continue;
        }
        $opener = $item;
        break;
      }
      $opener_idx = $i;

      if ($opener === null) {
        $openers_bottom[$char][$closer_len] = $position - 1;
        if (!($closer_flags & self::IS_START)) {
          $stack[$closer_idx] = new Stack\TextNode(
            $closer_text,
            $closer->getStartOffset(),
            $closer->getEndOffset(),
          );
        }
        ++$position;
        continue;
      }

      // Have an opener and closer pair

      $opener_text = $opener->getText();
      $strong = Str\length($opener_text) >= 2 && Str\length($closer_text) >= 2;

      $chomp = $strong ? 2 : 1;
      $opener_text = Str\slice($opener_text, $chomp);
      $closer_text = Str\slice($closer_text, $chomp);

      // We're going to throw away the existing opener, closer, and everything
      // in between; we build up the replacements here.
      $mid_nodes = vec[];

      if ($opener_text !== '') {
        // Remove the chars from the end of the delimiter as
        // `**foo*` is `*<em>foo</em>`, not `<em>*foo</em>`
        $mid_nodes[] = new Stack\DelimiterNode(
          $opener_text,
          $opener->getFlags(),
          $opener->getStartOffset(),
          $opener->getEndOffset() - $chomp,
        );
      } else {
        // We just ate up the last of this delimiter, so there's now none.
        // Adjust the stack offset, as we're effectively removing something
        // earlier in the stack than the current position
        $position--;
      }

      $first_content_idx = $opener_idx + 1;
      $last_content_idx = $closer_idx - 1;
      $content_length = ($last_content_idx - $first_content_idx) + 1;

      $mid_nodes[] =
        Vec\slice($stack, $first_content_idx, $content_length)
        |> self::consumeStackSlice($context, $$)
        |> new self($strong, $$)
        |> new Stack\EmphasisNode(
          $$,
          $opener->getEndOffset() - $chomp,
          $closer->getStartOffset() + $chomp,
        );
      $position -= $content_length;

      if ($closer_text !== '') {
        // Same as openers, however we take it from the start, as
        // `*foo**` is `<em>foo</em>*`, not `<em>foo*</em>`
        $mid_nodes[] = new Stack\DelimiterNode(
          $closer_text,
          $closer->getFlags(),
          $closer->getStartOffset() + $chomp,
          $closer->getEndOffset(),
        );
      }

      $stack = Vec\concat(
        Vec\take($stack, $opener_idx),
        $mid_nodes,
        Vec\drop($stack, $closer_idx + 1),
      );
    }

    return $stack;
  }

  private static function consumeStackSlice(
    Context $ctx,
    vec<Stack\Node> $nodes,
  ): vec<Inline> {
    return $nodes
      |> Vec\map($$, $node ==> $node->toInlines($ctx))
      |> Vec\flatten($$);
  }

  private static function findCloser(
    vec<Stack\Node> $in,
    int $position,
  ): ?int {
    $in = Vec\drop($in, $position);
    $offset = C\find_key(
      $in,
      $item ==>
        $item is Stack\DelimiterNode
        && $item->getFlags() & self::IS_END,
    );
    if ($offset === null) {
      return null;
    }
    $idx = $position + $offset;
    return $idx;
  }

  private static function consumeDelimiterRun(
    string $markdown,
    int $offset,
  ): (string, int) {
    $slice = Str\slice($markdown, $offset);
    $matches = [];
    \preg_match('/^(\\*+|_+)/', $slice, &$matches);
    $match = $matches[0];
    return tuple($match, $offset + Str\length($match));
  }

  private static function startsWithWhitespace(
    string $markdown,
    int $offset,
  ): bool {
    if ($offset === Str\length($markdown)) {
      return true;
    }
    return \preg_match(
      '/^'.self::UNICODE_WHITESPACE.'/u',
      Str\slice($markdown, $offset),
    ) === 1;
  }

  private static function endsWithWhitespace(
    string $markdown,
    int $offset,
  ): bool {
    if ($offset === 0) {
      return true;
    }
    return \preg_match(
      '/'.self::UNICODE_WHITESPACE.'$/u',
      Str\slice($markdown, 0, $offset),
    ) === 1;
  }

  private static function isLeftFlankingDelimiterRun(
    string $markdown,
    int $start_offset,
    int $end_offset,
  ): bool {
    $len = Str\length($markdown);
    $next = $end_offset === $len ? '' : $markdown[$end_offset];
    $previous = $start_offset === 0 ? '' : $markdown[$start_offset - 1];

    if (self::startsWithWhitespace($markdown, $end_offset)) {
      return false;
    }

    $previous_punctuation = C\contains_key(self::PUNCTUATION, $previous);
    $previous_whitespace = self::endsWithWhitespace($markdown, $start_offset);

    if ($previous_whitespace || $previous_punctuation) {
      return true;
    }

    // No intra-word `_` emphasis, but `*` is fine
    $next_punctuation = C\contains_key(self::PUNCTUATION, $next);
    if ((!$next_punctuation) && $markdown[$start_offset] !== '_') {
      return true;
    }

    return false;
  }

  private static function isRightFlankingDelimiterRun(
    string $markdown,
    int $start_offset,
    int $end_offset,
  ): bool {
    $len = Str\length($markdown);
    $next = $end_offset === $len ? '' : $markdown[$end_offset];
    $previous = $start_offset === 0 ? '' : $markdown[$start_offset - 1];

    if (self::endsWithWhitespace($markdown, $start_offset)) {
      return false;
    }

    $next_whitespace = self::startsWithWhitespace($markdown, $end_offset);
    $next_punctuation = C\contains_key(self::PUNCTUATION, $next);

    if ($next_whitespace || $next_punctuation) {
      return true;
    }

    // No intra-word `_` emphasis, but `*` is fine
    $previous_punctuation = C\contains_key(self::PUNCTUATION, $previous);
    if ((!$previous_punctuation) && $markdown[$start_offset] !== '_') {
      return true;
    }

    return false;
  }

  private static function consumeHigherPrecedence(
    Context $context,
    string $markdown,
    int $offset,
  ): ?(Inline, int) {
    foreach ($context->getInlineTypes() as $type) {
      if ($type === self::class) {
        return null;
      }

      $result = $type::consume($context, $markdown, $offset);
      if ($result !== null) {
        return $result;
      }
    }

    invariant_violation('should be unreachable');
  }

  private static function debugDump(
    string $markdown,
    int $position,
    vec<Stack\Node> $stack,
  ): void {
    \printf("-------------------- %d\n", $position);
    print(
      Vec\map_with_key(
        $stack,
        ($idx, $item) ==> '  '.$idx.'. '.$item->debugDump($markdown)
      )
      |> Str\join($$, "\n")
      |> $$."\n"
    );
  }

  private static function isStartOfRun(
    Context $context,
    string $markdown,
    int $offset,
  ): bool {
    $first = $markdown[$offset];
    if ($first !== '*' && $first !== '_') {
      return false;
    }

    if ($offset === 0) {
      return true;
    }

    $previous = $markdown[$offset - 1];
    if ($previous !== "\\" && $previous !== $first) {
      return true;
    }

    $previous = parse(
      $context,
      Str\slice($markdown, 0, $offset),
    );

    return C\last($previous) is BackslashEscape;
  }
}
