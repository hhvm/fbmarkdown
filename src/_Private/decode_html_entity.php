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

use namespace HH\Lib\{Regex, Str};

const string UNICODE_REPLACEMENT_CHARACTER = "\u{fffd}";

function decode_html_entity(string $string): ?(string, string, string) {
  if ($string[0] !== '&') {
    return null;
  }

  $matches = Regex\first_match(
    $string,
    re"/^&(#[0-9]{1,8}|#X[0-9a-f]{1,8}|[a-z][a-z0-9]*);/i",
  );
  if ($matches is null) {
    return null;
  }
  $match = $matches[0];

  $table = get_html_entity_table();
  $out = $table[$match] ?? null;
  if ($out !== null) {
    return tuple($match, $out, Str\strip_prefix($string, $match));
  }

  if ($match[1] !== '#') {
    return null;
  }

  $out = \html_entity_decode(
    $match,
    \ENT_HTML5,
    'UTF-8',
  );
  if ($out === $match) {
    return null;
  }
  if ($out === "\000") {
    $out = UNICODE_REPLACEMENT_CHARACTER;
  } else if (!\mb_check_encoding($out, 'UTF-8')) {
    $out = UNICODE_REPLACEMENT_CHARACTER;
  }
  return tuple($match, $out, Str\strip_prefix($string, $match));
}
