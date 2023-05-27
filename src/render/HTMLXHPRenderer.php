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

use namespace HH\Lib\{C, Str, Vec};
use namespace Facebook\XHP;
use type Facebook\XHP\Core\frag;
use type Facebook\XHP\HTML\{
  blockquote,
  br,
  code,
  del,
  em,
  h1,
  h2,
  h3,
  h4,
  h5,
  h6,
  hr,
  p,
  strong,
};
use type Facebook\Markdown\_Private\td_with_align;

class HTMLXHPRenderer extends Renderer<XHP\Core\node> {
  const keyset<classname<RenderFilter>> EXTENSIONS = keyset[
    TagFilterExtension::class,
  ];

  protected static function escapeContent(string $text): string {
    return _Private\plain_text_to_html($text);
  }

  protected static function escapeAttribute(string $text): string {
    return _Private\plain_text_to_html_attribute($text);
  }

  // This is the list from the reference implementation
  //hackfmt-ignore
  const keyset<string> URI_SAFE = keyset[
    '-', '_', '.', '+', '!', '*', "'", '(', ')', ';', ':', '%', '#', '@', '?',
    '=', ';', ':', '/', ',', '+', '&', '$',
    'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
    'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't',
    'u', 'v', 'w', 'x', 'y', 'z',
    '1', '2', '3', '4', '5', '6', '7', '8', '9', '0',
  ];

  protected static function escapeURIAttribute(string $text): string {
    // While the spec states that no particular method is required, we attempt
    // to match cmark's behavior so that we can run the spec test suite.
    $text = \html_entity_decode($text, \ENT_HTML5, 'UTF-8');

    $out = '';
    $len = Str\length($text);
    for ($i = 0; $i < $len; ++$i) {
      $char = $text[$i];
      if (C\contains_key(self::URI_SAFE, $char)) {
        $out .= $char;
        continue;
      }
      $out .= \urlencode($char);
    }
    $text = $out;

    return self::escapeAttribute($text);
  }

  <<__Override>>
  protected function renderNodes(vec<ASTNode> $nodes): XHP\Core\node {
    return $nodes
      |> Vec\map($$, $node ==> $this->render($node))
      |> Vec\filter($$, $line ==> $line !== '')
      |> _Private\xhp_join($$);
  }

  <<__Override>>
  protected function renderResolvedNode(ASTNode $node): XHP\Core\node {
    if ($node is RenderableAsXHP) {
      return $node->renderAsXHP($this->getContext(), $this);
    }

    // This interface is implemented by users of this library.
    // It must remain unchanged for backwards compatibility.
    // Ideally users would switch over to RenderableAsXHP.
    if ($node is RenderableAsHTML) {
      $string_renderer = new HTMLRenderer($this->getContext());
      return $node->renderAsHTML($this->getContext(), $string_renderer)
        |> _Private\DO_NOT_ESCAPE($$);
    }

    return parent::renderResolvedNode($node);
  }

  <<__Override>>
  protected function renderBlankLine(): XHP\Core\node {
    return <frag />;
  }

  <<__Override>>
  protected function renderBlockQuote(Blocks\BlockQuote $node): XHP\Core\node {
    return $node->getChildren()
      |> $this->renderNodes($$)
      |> <frag><blockquote>{"\n"}{$$}</blockquote>{"\n"}</frag>;
  }

  <<__Override>>
  protected function renderCodeBlock(Blocks\CodeBlock $node): XHP\Core\node {
    $extra = '';
    $info = $node->getInfoString();
    if ($info !== null) {
      $first = C\firstx(Str\split($info, ' '));
      $extra = ' class="language-'.self::escapeAttribute($first).'"';
    }
    $code = $node->getCode();
    if ($code !== '') {
      $code = self::escapeContent($code)."\n";
    }
    return '<pre><code'.$extra.'>'.$code."</code></pre>\n"
      |> _Private\DO_NOT_ESCAPE($$);
  }

  <<__Override>>
  protected function renderHeading(Blocks\Heading $node): XHP\Core\node {
    $children = $this->renderNodes($node->getHeading());
    switch ($node->getLevel()) {
      case 1:
        return <frag><h1>{$children}</h1>{"\n"}</frag>;
      case 2:
        return <frag><h2>{$children}</h2>{"\n"}</frag>;
      case 3:
        return <frag><h3>{$children}</h3>{"\n"}</frag>;
      case 4:
        return <frag><h4>{$children}</h4>{"\n"}</frag>;
      case 5:
        return <frag><h5>{$children}</h5>{"\n"}</frag>;
      case 6:
        return <frag><h6>{$children}</h6>{"\n"}</frag>;
    }
  }

  <<__Override>>
  protected function renderHTMLBlock(Blocks\HTMLBlock $node): XHP\Core\node {
    return $node->getCode()."\n" |> _Private\DO_NOT_ESCAPE($$);
  }

  <<__Override>>
  protected function renderLinkReferenceDefinition(
    Blocks\LinkReferenceDefinition $_def,
  ): XHP\Core\node {
    return <frag />;
  }

  protected function renderTaskListItemExtension(
    Blocks\ListOfItems $list,
    Blocks\TaskListItemExtension $item,
  ): string {
    $checked = $item->isChecked() ? ' checked=""' : '';
    $checkbox = '<input'.$checked.' disabled="" type="checkbox"> ';

    $children = $item->getChildren();
    $first = C\first($children);
    if ($first is Blocks\Paragraph) {
      $children[0] = new Blocks\Paragraph(
        Vec\concat(vec[new Inlines\RawHTML($checkbox)], $first->getContents()),
      );
    } else {
      $children = Vec\concat(vec[new Blocks\HTMLBlock($checkbox)], $children);
    }

    return $this->renderListItem(
      $list,
      new Blocks\ListItem($item->getNumber(), $children),
    );
  }

  protected function renderListItem(
    Blocks\ListOfItems $list,
    Blocks\ListItem $item,
  ): string {
    if ($item is Blocks\TaskListItemExtension) {
      return $this->renderTaskListItemExtension($list, $item);
    }

    $children = $item->getChildren();
    if (C\is_empty($children)) {
      return "<li></li>\n";
    }

    $content = '';

    if ($list->isTight()) {
      if (!C\first($children) is Blocks\Paragraph) {
        $content .= "\n";
      }

      $content .= $children
        |> Vec\map(
          $$,
          $child ==> {
            if ($child is Blocks\Paragraph) {
              return $this->renderNodes($child->getContents());
            }
            if ($child is Blocks\Block) {
              return $this->render($child)
                |> _Private\FORCE_RENDER($$)
                |> Str\trim($$)
                |> _Private\DO_NOT_ESCAPE($$);
            }
            return $this->render($child);
          },
        )
        |> _Private\xhp_join($$, () ==> "\n")
        |> _Private\FORCE_RENDER($$);
      if (!C\last($children) is Blocks\Paragraph) {
        $content .= "\n";
      }
    } else {
      $content = "\n"._Private\FORCE_RENDER($this->renderNodes($children));
    }

    return '<li>'.$content."</li>\n";
  }

  <<__Override>>
  protected function renderListOfItems(
    Blocks\ListOfItems $node,
  ): XHP\Core\node {
    $start = $node->getFirstNumber();
    if ($start === null) {
      $start = '<ul>';
      $end = '</ul>';
    } else if ($start === 1) {
      $start = '<ol>';
      $end = '</ol>';
    } else {
      $start = \sprintf('<ol start="%d">', $start);
      $end = '</ol>';
    }
    return $node->getItems()
      |> Vec\map($$, $item ==> $this->renderListItem($node, $item))
      |> Str\join($$, '')
      |> $start."\n".$$.$end."\n"
      |> _Private\DO_NOT_ESCAPE($$);
  }

  <<__Override>>
  protected function renderParagraph(Blocks\Paragraph $node): XHP\Core\node {
    return <frag><p>{$this->renderNodes($node->getContents())}</p>{"\n"}</frag>;
  }

  <<__Override>>
  protected function renderTableExtension(
    Blocks\TableExtension $node,
  ): XHP\Core\node {
    $html = "<table>\n".$this->renderTableHeader($node);

    $data = $node->getData();
    if (C\is_empty($data)) {
      return $html."</table>\n" |> _Private\DO_NOT_ESCAPE($$);
    }
    $html .= "\n<tbody>";

    $row_idx = -1;
    foreach ($data as $row) {
      ++$row_idx;
      $html .= "\n".$this->renderTableDataRow($node, $row_idx, $row);
    }
    return $html."</tbody></table>\n" |> _Private\DO_NOT_ESCAPE($$);
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
      $html .= '<th'.
        ($alignment ?? '').
        '>'.
        _Private\FORCE_RENDER($this->renderNodes($cell)).
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
    $html = '<tr>';
    for ($i = 0; $i < C\count($row); ++$i) {
      $cell = $row[$i];

      $html .= "\n".
        _Private\FORCE_RENDER(
          $this->renderTableDataCell($table, $row_idx, $i, $cell),
        );
    }
    $html .= "\n</tr>";
    return $html;
  }

  protected function renderTableDataCell(
    Blocks\TableExtension $table,
    int $_row_idx,
    int $col_idx,
    Blocks\TableExtension::TCell $cell,
  ): XHP\Core\node {
    $align =
      $table->getColumnAlignments()[$col_idx] |> $$ is null ? null : $$.'';
    return
      <td_with_align align={$align}>{$this->renderNodes($cell)}</td_with_align>;
  }

  <<__Override>>
  protected function renderThematicBreak(): XHP\Core\node {
    return <frag><hr />{"\n"}</frag>;
  }

  <<__Override>>
  protected function renderAutoLink(Inlines\AutoLink $node): XHP\Core\node {
    $href = self::escapeURIAttribute($node->getDestination());
    $text = self::escapeContent($node->getText());
    $noFollowUgcTag =
      $this->getContext()->areLinksNoFollowUGC() ? ' rel="nofollow ugc"' : '';
    return '<a href="'.$href.'"'.$noFollowUgcTag.'>'.$text.'</a>'
      |> _Private\DO_NOT_ESCAPE($$);
  }

  <<__Override>>
  protected function renderInlineWithPlainTextContent(
    Inlines\InlineWithPlainTextContent $node,
  ): XHP\Core\node {
    return <frag>{$node->getContent()}</frag>;
  }

  <<__Override>>
  protected function renderCodeSpan(Inlines\CodeSpan $node): XHP\Core\node {
    return <code>{$node->getCode()}</code>;
  }

  <<__Override>>
  protected function renderEmphasis(Inlines\Emphasis $node): XHP\Core\node {
    $children = Vec\map($node->getContent(), $item ==> $this->render($item))
      |> _Private\xhp_join($$);
    return
      $node->isStrong() ? <strong>{$children}</strong> : <em>{$children}</em>;
  }

  <<__Override>>
  protected function renderHardLineBreak(): XHP\Core\node {
    return <frag><br />{"\n"}</frag>;
  }

  <<__Override>>
  protected function renderImage(Inlines\Image $node): XHP\Core\node {
    $title = $node->getTitle();
    if ($title !== null) {
      $title = ' title="'.self::escapeAttribute($title).'"';
    }
    $src = self::escapeURIAttribute($node->getSource());
    $text = $node->getDescription()
      |> Vec\map($$, $child ==> $child->getContentAsPlainText())
      |> Str\join($$, '');
    // Needs to always be present for spec tests to pass
    $alt = ' alt="'.self::escapeAttribute($text).'"';
    return '<img src="'.$src.'"'.$alt.($title ?? '').' />'
      |> _Private\DO_NOT_ESCAPE($$);
  }

  <<__Override>>
  protected function renderLink(Inlines\Link $node): XHP\Core\node {
    $title = $node->getTitle();
    if ($title !== null) {
      $title = ' title="'.self::escapeAttribute($title).'"';
    }
    $href = self::escapeURIAttribute($node->getDestination());
    $text = $node->getText()
      |> Vec\map($$, $child ==> $this->render($child))
      |> _Private\xhp_join($$)
      |> _Private\FORCE_RENDER($$);
    $noFollowUgcTag =
      $this->getContext()->areLinksNoFollowUGC() ? ' rel="nofollow ugc"' : '';
    return
      '<a href="'.$href.'"'.$noFollowUgcTag.''.($title ?? '').'>'.$text.'</a>'
      |> _Private\DO_NOT_ESCAPE($$);
  }

  <<__Override>>
  protected function renderRawHTML(Inlines\RawHTML $node): XHP\Core\node {
    return _Private\DO_NOT_ESCAPE($node->getContent());
  }

  <<__Override>>
  protected function renderSoftLineBreak(): XHP\Core\node {
    return <frag>{"\n"}</frag>;
  }

  <<__Override>>
  protected function renderStrikethroughExtension(
    Inlines\StrikethroughExtension $node,
  ): XHP\Core\node {
    return $node->getChildren()
      |> Vec\map($$, $child ==> $this->render($child))
      |> _Private\xhp_join($$)
      |> <del>{$$}</del>;
  }
}
