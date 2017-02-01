<?php
/**
 * @file help.inc.php
 *
 * Here belongs all default No2 helpers.
 *
 * @note
 *   Before creating any function here, please have a tought for name collision
 *   (namespacing with no2 is an option), although any function that could bear
 *   the be prefixed shouldn't be here. The idea is that function that live in
 *   no2 core belongs here the probability that they're using in EVERY project
 *   is near 1.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */

/**
 * get the application host.
 *
 * @return
 *   a string with the full HTTP hostname.
 */
function http_host()
{
    return $_SERVER['HTTP_HOST'];
}

/**
 * Sanitize a string for display.
 *
 * Escape HTML tags for a safer display. This function assume UTF-8
 * encoding.
 *
 * @param $string
 *   The unsafe string.
 *
 * @return
 *   a string that can be safely presented to echo or print for output.
 */
function h($string)
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * sane JSON encoding.
 *
 * @see https://wiki.php.net/rfc/json_preserve_fractional_part
 * @see https://secure.php.net/manual/en/function.json-encode.php
 * @see https://secure.php.net/manual/en/json.constants.php
 */
function sane_json_encode($value, $options = 0, $depth = 512)
{
    // NOTE: since PHP 5.6.6
    if (defined('JSON_PRESERVE_ZERO_FRACTION')) {
        $options |= JSON_PRESERVE_ZERO_FRACTION;
    }
    return json_encode($value, $options, $depth);
}

/**
 * "HTML safe" JSON encode.
 *
 * When printing in HTML context we need to escape characters that could be
 * interpreted by HTML, like LESS-THAN SIGN, GREATER-THAN SIGN etc.
 *
 * @see https://secure.php.net/manual/en/function.json-encode.php
 * @see https://secure.php.net/manual/en/json.constants.php
 */
function html_json_encode($value, $options = 0, $depth = 512)
{
    //            < and >           &              '               "
    $options |= JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    return sane_json_encode($value, $options, $depth);
}

/**
 * Some serious modulo function.
 * * http://mindspill.net/computing/cross-platform-notes/php/php-modulo-operator-returns-negative-numbers/
 */
function truemod($num, $mod)
{
    return ($mod + ($num % $mod)) % $mod;
}

/**
 * This function is a workaround trying to fix the suckness level of PHP, which
 * is fairly high (if not the highest of all programming languages ever designed).
 *
 * "inspired" by http://php.net/manual/fr/function.empty.php#107819
 */
function _empty($val)
{
    return empty($val);
}

/**
 * A correct uuidv4 generator in PHP.
 *
 * see http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid/15875555#15875555
 */
function uuidv4()
{
    $data = openssl_random_pseudo_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * @param $str
 *   the string to match.
 *
 * @return
 *   true if the given string is a UUIDv4, false otherwise.
 */
function is_uuidv4($str)
{
    $regexp = '/^[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-4[A-Fa-f0-9]{3}-[ABab89][A-Fa-f0-9]{3}-[A-Fa-f0-9]{12}$/';
    return (preg_match($regexp, $str) ? true : false);
}

/**
 * return true if the session has been started, false otherwise.
 *
 * see http://stackoverflow.com/questions/6249707/check-if-php-session-has-already-started#answer-18542272
 */
function session_active()
{
    if (function_exists('session_status')) // PHP 5.4+
        $started = (session_status() === PHP_SESSION_ACTIVE);
    else
        $started = (session_id() !== '');
    return $started;
}

/**
 * convert an ISO-8601 formated string to a PHP DateTime object.
 *
 * see http://stackoverflow.com/questions/14849446/php-parse-date-in-iso-format
 *
 * @bugs
 *   Milliseconds will be lost.
 *
 * @return
 *   A DateTime object or false on error.
 */
function iso8601_to_datetime($str)
{
    $i = strtotime($str);
    $d = new DateTime();
    return $d->setTimestamp($i);
}

/**
 * convert a PHP DateTime object to an ISO-8601 string
 *
 * @see
 *   http://php.net/manual/en/class.datetime.php#datetime.constants.iso8601
 *
 * @return
 *   A string.
 */
function datetime_to_iso8601($d)
{
    return $d->format(DateTime::ATOM);
}

/**
 * Test if a string is encoded in UTF-8.
 *
 * @see
 *   http://www.php.net/manual/fr/function.mb-detect-encoding.php#50087
 *
 * @param $string (required)
 *   The string to test.
 *
 * @return
 *   true if $string is valid UTF-8 and false otherwise.
 */
function is_utf8($string)
{
    // From http://w3.org/International/questions/qa-forms-utf-8.html
    return preg_match('%^(?:
          [\x09\x0A\x0D\x20-\x7E]            # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
    )*$%xs', $string);
}

/**
 * array_map like for stdClass.
 */
function object_map($func, $obj)
{
    return (object)array_map($func, (array)$obj);
}
