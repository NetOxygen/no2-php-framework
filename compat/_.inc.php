<?php // mostly for HHVM

if (!function_exists('_')) {
    function _($string) {
        return $string;
    }
}
