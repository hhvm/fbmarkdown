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

use type Facebook\Markdown\Blocks\TableExtensionColumnAlignment;
use namespace HH\Lib\{C, Math, Str, Vec};

/** Re-create Markdown from the AST */
class MarkdownRenderer extends Renderer<string> {
  private ?string $outContext = null;

  const keyset<classname<RenderFilter>> EXTENSIONS = keyset[
    TagFilterExtension::class,
  ];

  <<__Override>>
  protected function renderNodes(vec<ASTNode> $nodes): string {
    $this->outContext = '';
    return $nodes
      |> Vec\map(
        $$,
        $node ==> {
          $content = $this->render($node);
          if ($node instanceof Blocks\Block) {
            $content = $content."\n\n";
          }
          $this->outContext .= $content;
          return $content;
        },
      )
      |> Vec\filter($$, $line ==> $line !== '')
      |> Str\join($$, '')
      |> Str\strip_suffix($$, "\n\n");
  }

  <<__Override>>
  protected function renderBlankLine(): string {
    return "\n";
  }

  <<__Override>>
  protected function renderBlockQuote(Blocks\BlockQuote $node): string {
    return $node->getChildren()
      |> $this->renderNodes($$)
      |> Str\split($$, "\n")
      |> Vec\map($$, $line ==> '> '.$line)
      |> Str\join($$, "\n");
  }

  <<__Override>>
  protected function renderCodeBlock(Blocks\CodeBlock $node): string {
    if (Str\contains($node->getCode(), '```')) {
      $separator = '~~~';
    } else {
      $separator = '```';
    }
    $info = $node->getInfoString();
    if ($info !== null) {
      $info = ' '.$info;
    }

    return $separator.$info."\n".$node->getCode()."\n".$separator;
  }

  <<__Override>>
  protected function renderHeading(Blocks\Heading $node): string {
    $level = $node->getLevel();
    $content = $node->getHeading()
      |> $this->renderNodes($$);
    if (!Str\contains($content, "\n")) {
      return Str\repeat('#', $node->getLevel()).' '.$content;
    }
    switch ($level) {
      case 1:
        $marker = '===';
        break;
      case 2:
        $marker = '---';
        break;
      default:
        invariant_violation(
          "Can't handle a multi-line level %d heading.",
          $level,
        );
    }
    return $content."\n".$marker;
  }

  <<__Override>>
  protected function renderHTMLBlock(Blocks\HTMLBlock $node): string {
    return $node->getCode();
  }

  <<__Override>>
  protected function renderLinkReferenceDefinition(
    Blocks\LinkReferenceDefinition $def,
  ): string {
    // This doesn't end up mattering as we currently normalize out the link
    // reference definitions when constructing the AST; keeping them in case
    // this is mixed with other markdown, or if we end up creating a separate
    // ReferenceLink AST node that renderers need to deal with.
    $md = \sprintf('[%s]: <%s>', $def->getLabel(), $def->getDestination());
    $title = $def->getTitle();
    if ($title === null) {
      return $md;
    }

    return $this->renderNodes($title)
      |> Str\replace($$, "'", "\\'")
      |> $md." '".$$."'";
  }

  protected function renderListItem(
    int $list_count,
    Blocks\ListOfItems $list,
    Blocks\ListItem $item,
  ): string {

    $num = $list->getFirstNumber();
    if ($num === null) {
      /* This is a single loose list:
       *
       * - foo
       * - bar
       *
       * - baz
       *
       * This is two tight lists:
       *
       * - foo
       * - bar
       *
       * + baz
       */
      $seps = vec['-', '+', '*'];
      $sep = $seps[$list_count % 3].' ';
    } else {
      /* One loose list:
       *
       * 1. foo
       * 2. bar
       *
       * 3. baz
       *
       * Two tight lists:
       *
       * 1. foo
       * 2. bar
       * 3) baz
       */
      $seps = vec['.', ')'];
      $sep = $num.$seps[$list_count % 2].' ';
    }
    $leading = Str\length($sep);

    if ($item instanceof Blocks\TaskListItemExtension) {
      $sep .= \sprintf('[%s] ', $item->isChecked() ? 'x' : ' ');
    }

    if ($list->isLoose()) {
      $content = $item->getChildren()
        |> $this->renderNodes($$)
        |> $$."\n";
    } else {
      $content = $item->getChildren()
        |> Vec\map(
          $$,
          $child ==> {
            if ($child instanceof Blocks\Paragraph) {
              return $this->renderNodes($child->getContents());
            }
            if ($child instanceof Blocks\Block) {
              return Str\trim($this->render($child));
            }
            return $this->render($child);
          },
        )
        |> Str\join($$, "\n");
    }
    return $content
      |> Str\split($$, "\n")
      |> Vec\map(
        $$,
        $line ==> ($line === '') ? $line : (Str\repeat(' ', $leading).$line),
      )
      |> Str\join($$, "\n")
      |> Str\slice($$, Math\minva(Str\length($$), $leading))
      |> $sep.$$;
  }

  // Used to rotate between separators
  private int $numberOfLists = 0;

  <<__Override>>
  protected function renderListOfItems(Blocks\ListOfItems $node): string {
    $this->numberOfLists++;
    $this_list = $this->numberOfLists;
    return $node->getItems()
      |> Vec\map(
        $$,
        $item ==> $this->renderListItem($this_list, $node, $item),
      )
      |> Str\join($$, "\n");
  }

  <<__Override>>
  protected function renderParagraph(Blocks\Paragraph $node): string {
    $ctx = new UnparsedBlocks\Context();
    return $this->renderNodes($node->getContents())
      |> Str\split($$, "\n")
      |> Vec\map(
        $$,
        $line ==> {
          $parsed = UnparsedBlocks\parse($ctx, $line)->getChildren();
          if (!C\firstx($parsed) instanceof UnparsedBlocks\Paragraph) {
            return "    ".$line;
          }
          if (\preg_match('/^ {0,3}[=-]+ *$/', $line)) {
            return "\\".$line;
          }
          return $line;
        },
      )
      |> Str\join($$, "\n");
  }

  <<__Override>>
  protected function renderTableExtension(
    Blocks\TableExtension $table,
  ): string {
    return $table->getData()
      |> Vec\map($$, $row ==> $this->renderTableDataRow($row))
      |> Str\join($$, "\n")
      |> $this->renderTableHeader($table)."\n".$$;
  }

  protected function renderTableHeader(Blocks\TableExtension $node): string {
    $header_row = $node->getHeader()
      |> Vec\map($$, $cell ==> $this->renderTableDataCell($cell))
      |> Str\join($$, ' | ')
      |> '| '.$$.' |';

    $delimiter_row = $node->getColumnAlignments()
      |> Vec\map(
        $$,
        $alignment ==> {
          if ($alignment === null) {
            return '-';
          }
          switch ($alignment) {
            case TableExtensionColumnAlignment::LEFT:
              return ':-';
            case TableExtensionColumnAlignment::RIGHT:
              return '-:';
            case TableExtensionColumnAlignment::CENTER:
              return ':-:';
          }
        },
      )
      |> Str\join($$, ' | ')
      |> '| '.$$.' |';

    return $header_row."\n".$delimiter_row;
  }

  protected function renderTableDataRow(
    Blocks\TableExtension::TRow $row,
  ): string {
    return $row
      |> Vec\map($$, $cell ==> $this->renderTableDataCell($cell))
      |> Str\join($$, ' | ')
      |> '| '.$$.' |';
  }

  protected function renderTableDataCell(
    Blocks\TableExtension::TCell $cell,
  ): string {
    return $this->renderNodes($cell)
      |> Str\replace($$, "|", "\\|");
  }

  <<__Override>>
  protected function renderThematicBreak(): string {
    return "\n***";
  }

  <<__Override>>
  protected function renderAutoLink(Inlines\AutoLink $node): string {
    if ($node instanceof Inlines\AutoLinkExtension) {
      return $node->getText();
    }
    return '<'.$node->getText().'>';
  }

  <<__Override>>
  protected function renderInlineWithPlainTextContent(
    Inlines\InlineWithPlainTextContent $node,
  ): string {
    if ($node instanceof Inlines\BackslashEscape) {
      return "\\".$node->getContent();
    }
    if ($node instanceof Inlines\EntityReference) {
      // This matters if the entity reference is for whitespace: if we print
      // it out raw, we might accidentally create an indented code block, or
      // continue a more deeply nested block than we should.
      return \mb_encode_numericentity(
        $node->getContent(),
        // start, end, offset, mask
        [0, 0xffff, 0, 0xffff],
        'UTF-8',
      );
    }
    return $node->getContent();
  }

  <<__Override>>
  protected function renderCodeSpan(Inlines\CodeSpan $node): string {
    $code = $node->getCode();
    $len = Str\length((string)$this->outContext) + Str\length($code);

    $sep = '`';
    for ($sep_len = 1; $sep_len <= $len + 1; ++$sep_len) {
      $sep = Str\repeat('`', $sep_len);
      if (Str\contains($code, $sep)) {
        continue;
      }
      if (Str\contains((string)$this->outContext, $sep)) {
        continue;
      }
      break;
    }

    return $sep.' '.Str\trim($code).' '.$sep;
  }

  <<__Override>>
  protected function renderEmphasis(Inlines\Emphasis $node): string {
    $content = $node->getContent()
      |> Vec\map($$, $item ==> $this->render($item))
      |> Str\join($$, '');

    if (Str\contains($content, '*')) {
      $tag = '_';
    } else {
      $tag = '*';
    }
    if ($node->isStrong()) {
      $tag .= $tag;
    }
    return $tag.$content.$tag;
  }

  <<__Override>>
  protected function renderHardLineBreak(): string {
    return "\\\n";
  }

  <<__Override>>
  protected function renderImage(Inlines\Image $node): string {
    $t = $node->getTitle();
    return Str\format(
      "![%s](<%s>%s)",
      $this->renderNodes($node->getDescription()),
      $node->getSource(),
      $t === null ? '' : (' "'.$t.'"'),
    );
  }

  <<__Override>>
  protected function renderLink(Inlines\Link $node): string {
    $text = $this->renderNodes($node->getText());
    $destination = $node->getDestination();
    $title = $node->getTitle();
    if ($title === null) {
      return \sprintf('[%s](<%s>)', $text, $destination);
    }
    return \sprintf(
      "[%s](<%s> '%s')",
      $text,
      $destination,
      Str\replace($title, "'", "\\'"),
    );
  }

  <<__Override>>
  protected function renderRawHTML(Inlines\RawHTML $node): string {
    return $node->getContent();
  }

  <<__Override>>
  protected function renderSoftLineBreak(): string {
    return "\n";
  }

  <<__Override>>
  protected function renderStrikethroughExtension(
    Inlines\StrikethroughExtension $node,
  ): string {
    $children = $node->getChildren()
      |> Vec\map($$, $child ==> $this->render($child))
      |> Str\join($$, '');
    return '~'.$children.'~';
  }
}
