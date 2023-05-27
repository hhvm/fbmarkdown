<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\Markdown\_Private;

use namespace HH\Lib\{C, Str};

function escape_uri_attribute(string $uri): EscapedAttribute {
  // While the spec states that no particular method is required, we attempt
  // to match cmark's behavior so that we can run the spec test suite.
  $uri = \html_entity_decode($uri, \ENT_HTML5, 'UTF-8');

  $out = '';
  $len = Str\length($uri);
  for ($i = 0; $i < $len; ++$i) {
    $char = $uri[$i];
    if (C\contains_key(URI_SAFE, $char)) {
      $out .= $char;
      continue;
    }
    $out .= \urlencode($char);
  }
  $uri = $out;

  return new EscapedAttribute(plain_text_to_html_attribute($uri));
}
