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

use namespace HH\Lib\{C, Str, Vec};

/** Re-create Markdwon from the AST */
class MarkdownRenderer extends Renderer<string> {
  const keyset<classname<RenderFilter>> EXTENSIONS = keyset[
    TagFilterExtension::class,
  ];

  <<__Override>>
  protected function renderNodes(vec<ASTNode> $nodes): string {
    return $nodes
      |> Vec\map($$, $node ==> $this->render($node))
      |> Vec\filter($$, $line ==> $line !== '')
      |> Str\join($$, '');
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
    if (Str\contains($node->getCode(), "---")) {
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
      return Str\repeat('#', $node->getLevel()).' '.$content."\n";
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
    return $content."\n".$marker."\n";
  }

  <<__Override>>
  protected function renderHTMLBlock(Blocks\HTMLBlock $node): string {
    return $node->getCode();
  }

  <<__Override>>
  protected function renderLinkReferenceDefinition(
    Blocks\LinkReferenceDefinition $def,
  ): string {
    return __FUNCTION__;
  }

  protected function renderTaskListItemExtension(
    Blocks\ListOfItems $list,
    Blocks\TaskListItemExtension $item,
  ): string {
    return __FUNCTION__;
  }

  protected function renderListItem(
    Blocks\ListOfItems $list,
    Blocks\ListItem $item,
  ): string {
    if ($item instanceof Blocks\TaskListItemExtension) {
      return $this->renderTaskListItemExtension($list, $item);
    }

    $sep = $list->getFirstNumber();
    if ($sep === null) {
      $sep = '- ';
    } else {
      $sep = $sep.'. ';
    }
    $leading = Str\length($sep);


    if ($list->isLoose()) {
      $content = $item->getChildren()
       |> $this->renderNodes($$);
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
      |> Vec\map($$, $line ==> Str\repeat(' ', $leading).$line)
      |> Str\join($$, "\n")
      |> Str\slice($$, $leading)
      |> $sep.$$;
  }

  <<__Override>>
  protected function renderListOfItems(Blocks\ListOfItems $node): string {
    return $node->getItems()
      |> Vec\map($$, $item ==> $this->renderListItem($node, $item))
      |> Str\join($$, "\n");
  }

  <<__Override>>
  protected function renderParagraph(Blocks\Paragraph $node): string {
    $ctx = new UnparsedBlocks\Context();
    \var_dump($node);
    return $this->renderNodes($node->getContents())
      |> Str\split($$, "\n")
      |> Vec\map(
        $$,
        $line ==> {
          $parsed = UnparsedBlocks\parse($ctx, $line)->getChildren();
          if (!C\firstx($parsed) instanceof UnparsedBlocks\Paragraph)  {
            return "    ".$line;
          }
          return $line;
        },
      )
      |> Str\join($$, "\n")
      |> $$."\n\n";
  }

  <<__Override>>
  protected function renderTableExtension(Blocks\TableExtension $node): string {
    $html = "<table>\n".$this->renderTableHeader($node);

    $data = $node->getData();
    if (C\is_empty($data)) {
      return $html."</table>\n";
    }
    $html .= "\n<tbody>";

    $row_idx = -1;
    foreach ($data as $row) {
      ++$row_idx;
      $html .= "\n".$this->renderTableDataRow($node, $row_idx, $row);
    }
    return $html."</tbody></table>\n";
  }

  protected function renderTableHeader(Blocks\TableExtension $node): string {
    $html = "<thead>\n<tr>\n";

    $alignments = $node->getColumnAlignments();
    $header = $node->getHeader();
    for ($i = 0; $i < C\count($header); ++$i) {
      $cell = $header[$i];
      $alignment = $alignments[$i];
      if ($alignment !== null) {
        $alignment = ' align="'.$alignment.'"';
      }
      $html .=
        '<th'.$alignment.'>'.
        $this->renderNodes($cell).
        "</th>\n";
    }
    $html .= "</tr>\n</thead>";
    return $html;
  }

  protected function renderTableDataRow(
    Blocks\TableExtension $table,
    int $row_idx,
    Blocks\TableExtension::TRow $row,
  ): string {
    $html = "<tr>";
    for ($i = 0; $i < C\count($row); ++$i) {
      $cell = $row[$i];

      $html .= "\n".$this->renderTableDataCell($table, $row_idx, $i, $cell);
    }
    $html .= "\n</tr>";
    return $html;
  }

  protected function renderTableDataCell(
    Blocks\TableExtension $table,
    int $row_idx,
    int $col_idx,
    Blocks\TableExtension::TCell $cell,
  ): string {
    $alignment = $table->getColumnAlignments()[$col_idx];
    if ($alignment !== null) {
      $alignment = ' align="'.$alignment.'"';
    }
    return
      "<td".$alignment.'>'.
      $this->renderNodes($cell).
      "</td>";
  }

  <<__Override>>
  protected function renderThematicBreak(): string {
    return "\n***\n";
  }

  <<__Override>>
  protected function renderAutoLink(Inlines\AutoLink $node): string {
    return __FUNCTION__;
  }

  <<__Override>>
  protected function renderInlineWithPlainTextContent(
    Inlines\InlineWithPlainTextContent $node,
  ): string {
    if ($node instanceof Inlines\BackslashEscape) {
      return "\\".$node->getContent();
    }
    return $node->getContent();
  }

  <<__Override>>
  protected function renderCodeSpan(Inlines\CodeSpan $node): string {
    return '`'.$node->getCode().'`';
  }

  <<__Override>>
  protected function renderEmphasis(Inlines\Emphasis $node): string {
    $tag = $node->isStrong() ? '**' : '*';
    return $node->getContent()
      |> Vec\map($$, $item ==> $this->render($item))
      |> Str\join($$, '')
      |> $tag.$$.$tag;
  }

  <<__Override>>
  protected function renderHardLineBreak(): string {
    return "<br />\n";
  }

  <<__Override>>
  protected function renderImage(Inlines\Image $node): string {
    return __FUNCTION__;
  }

  <<__Override>>
  protected function renderLink(Inlines\Link $node): string {
    return __FUNCTION__;
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
  protected function renderStrikethroughExtension(Inlines\StrikethroughExtension $node): string {
    $children = $node->getChildren()
      |> Vec\map($$, $child ==> $this->render($child))
      |> Str\join($$, '');
    return '~'.$children.'~';
  }
}
