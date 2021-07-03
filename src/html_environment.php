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

function html_environment(): Environment<string> {
  $parser = new ParserContext();
  $context = new RenderContext();
  $renderer = new HTMLRenderer($context);

  return new Environment($parser, $context, $renderer);
}

function unsafe_html_environment(): Environment<string> {
  $parser = new ParserContext();
  $context = new RenderContext();
  $renderer = new HTMLRenderer($context);

  $parser->enableHTML_UNSAFE();

  return new Environment($parser, $context, $renderer);
}
