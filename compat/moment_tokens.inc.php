<?php
// translated from https://github.com/niwinz/moment-tokens/blob/master/moment-tokens.js

function strftime2momentjs($format)
{
    static $strftimeFormats = [];

    $translateStrftimeToMoment = function ($items) {
        $item = $items[0];
        if (substr($item, 0, 2) === '%%')
            return preg_replace('%%', '%', $item);
        switch ($item) {
        case "%a":
            return "ddd";
        case "%A":
            return "dddd";
        case "%h":
        case "%b":
            return "MMM";
        case "%B":
            return "MMMM";
        case "%c": // XXX: probably broken
            return "LLLL";
        case "%d":
            return "DD";
        case "%j":
            return "DDDD";
        case "%e":
            return "D";
        case "%m":
            return "MM";
        case "%p":
            return "A";
        case "%P":
            return "a";
        case "%S":
            return "ss";
        case "%M":
            return "mm";
        case "%H":
            return "HH";
        case "%I":
            return "hh";
        case "%w":
            return "d";
        case "%W":
        case "%U":
            return "ww";
        case "%x": // XXX: probably broken
            return "LL";
        case "%X":// XXX: probably broken
            return "LT";
        case "%g":
        case "%y":
            return "YY";
        case "%G":
        case "%Y":
            return "YYYY";
        case "%z":
            return "ZZ";
        case "%Z":// XXX: probably broken
            return "z";
        default:
            return $item;
        }
        /* NOTREACHED */
    };

    if (!array_key_exists($format, $strftimeFormats)) {
        $strftimeFormats[$format] = preg_replace_callback('/%./', $translateStrftimeToMoment, $format);
    }
    return $strftimeFormats[$format];
}
