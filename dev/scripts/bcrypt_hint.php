#!/usr/bin/env php
<?php
/**
 * @file bcrypt_hint.php
 *
 * simple script to compute the "optimal" bcrypt cost that should be used on
 * this host.
 */
require_once(dirname(__FILE__) . '/../../vendor/autoload.php');

define('MIN_SEC', 0.2);
define('MAX_SEC', 0.5);

function elapsed_time($func) {
    $start = microtime(true);
    $func();
    return (microtime(true) - $start);
}

$results = [];
$t       = 0;
$cost    = 4;

while ($t < MAX_SEC) {
    $t = elapsed_time(function () use ($cost) {
        password_hash('secret', PASSWORD_BCRYPT, ['cost' => $cost]);
    });
    $results[] = ['cost' => $cost, 'time' => $t];
    $cost++;
}

foreach ($results as $r) {
    $is_cool = ($r['time'] > MIN_SEC && $r['time'] < MAX_SEC);
    printf("cost %d: %.3fs%s\n", $r['cost'], $r['time'], ($is_cool ? ' <--- cool' : ''));
}

