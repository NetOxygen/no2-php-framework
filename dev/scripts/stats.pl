#!/usr/bin/env perl
# Simple code statistics script.

use feature qw(say);

while (<>) {
    $total   += 1;
    $comment += 1 if m{^\s*(?:/\*|//|\*)};
    $empty   += 1 if /^\s*$/;
}

$code = $total - $empty - $comment;
# avoid a nasty division by 0.
$code = 1 if ($code eq 0);

format =
total:    @###
          $total
code:     @###
          $code
empty:    @###
          $empty
comment:  @###
          $comment
------------------------
comment/code ratio: @.##
                    $comment / $code
.

write();
