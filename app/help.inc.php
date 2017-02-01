<?php
/**
 * @file help.inc.php
 *
 * Here belongs all the helper function for this project.
 *
 * Most of the functions are suffixed with _to_s meaning that they convert an
 * application (or database) internal representation of a value into a user
 * friendly (localized and/or translated), displayable value.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */

/*
 * find a template file.
 *
 * @note
 *   This function doesn't check the returned path. A user could inject a path
 *   (with `..'). It is the responsibility of the caller to ensure a clean
 *   <code>$name</code> parameter.
 *
 * @param $name
 *   The name of the template file (can include slash `/' to find file in
 *   directories).
 *
 * @return
 *   The template file path. If the file doesn't exist or is not readable, NULL
 *   is returned.
 */
function template($name) {
    $target = APPDIR . "/views/{$name}.tpl.php";
    if (!is_file($target) || !is_readable($target)) {
        /*
         * don't log an error here. It could be a controller just wanting to
         * test if a template file exist.
         */
        $target = NULL;
    }

    return $target;
}

/**
 * a translation function.
 *
 * Use the global $translator or fallback to _() (usually gettext). If an
 * object is given, it is expected to have a property key current_lang() and
 * its value is returned.
 */
function t()
{
    global $translator;
    $argv = func_get_args();
    if (count($argv) === 1 && is_object($argv[0])) {
        $prop = current_lang();
        $ret  = $argv[0]->$prop;
    } elseif (isset($translator)) {
        $ret = call_user_func_array([$translator, 'trans'], $argv);
    } else {
        $ret = call_user_func_array('_', $argv);
    }
    return $ret;
}

/*
 * very dumb helper "chaining" t() in h().
 */
function ht()
{
    $argv = func_get_args();
    return h(call_user_func_array('t', $argv));
}

/**
 * translate a locale to lang.
 *
 * @param $locale (string)
 *   The configured locale (case sensitive).
 *
 * @return
 *   The locale associated to the given lang.
 */
function locale_to_lang($locale)
{
    $lang = null;
    $flipped = array_flip(AppConfig::get('l10n.lang2locale'));
    if (array_key_exists($locale, $flipped)) {
        $lang = strtolower($flipped[$locale]);
    }
    return $lang;
}

/**
 * translate a lang to a locale.
 *
 * @param $lang (string)
 *   The configured lang (case insensitive).
 *
 * @return
 *   The lang's matching locale.
 */
function lang_to_locale($lang)
{
    return AppConfig::get(array('l10n', 'lang2locale', strtolower($lang)));
}

/**
 * return the current lang used.
 */
function current_lang()
{
    return locale_to_lang(current_locale());
}

/**
 * set the current lang used.
 *
 * @param $new
 *   the lang to switch to.
 *
 * @return
 *   the current lang after the switch. If the return value doesn't match the
 *   requested lang the switch has failed.
 */
function current_lang_is($new)
{
    current_locale_is(lang_to_locale($new));
    return current_lang();
}

/**
 * return the current locale.
 */
function current_locale()
{
    // try to use the session's locale.
    if (array_key_exists('_no2_locale', $_SESSION)) {
        $locale = $_SESSION['_no2_locale'];
    } else { // fallback to default locale.
        $locale = AppConfig::get('l10n.default_locale');
    }
    return $locale;
}

/**
 * set the current locale.
 *
 * NOTE: if locale switching does not work, check if the server support the
 * requested locale using something `locale -a | grep $locale'
 *
 * @param $new
 *   The locale to switch to.
 *
 * @return
 *   the current locale after the switch. If the return value doesn't match the
 *   requested locale the switch has failed.
 */
function current_locale_is($new)
{
    global $translator;
    $old = current_locale();
    $translations = AppConfig::get('l10n.translations');
    if (array_key_exists($new, $translations)) {
        // we do have a translation for the requested locale, do the switch.
        setlocale(LC_ALL, $new);
        if (isset($translator)) {
            $translator->setLocale($new);
        }
        $_SESSION['_no2_locale'] = $new;
    }
    return current_locale();
}

/**
 * Setup a new Translator using the configuration and the given locale.
 */
function create_translator($locale)
{
    $translator = new Symfony\Component\Translation\Translator($locale);
    $translator->addLoader('yaml', new Symfony\Component\Translation\Loader\YamlFileLoader());
    foreach (AppConfig::get('l10n.translations') as $loc => $file) {
        $translator->addResource('yaml', $file, $loc);
    }

    $GLOBALS['translator'] = $translator;
    current_locale_is($locale);

    return $translator;
}

/**
 * nice shortcut to User::current()
 */
function current_user() {
    return User::current();
}

/**
 * sanitize a text string intended for a textarea tag handled by a wysiwyg editor.
 *
 * @note The current implementation use CKEditor.
 *
 * @param $text
 *   The string to sanitize.
 *
 * @return
 *   A text string that can be safely printed in a textarea tag.
 */
function wysiwyg($text) {
    return $text;
}

/**
 * render in HTML an error message for a model field.
 *
 * A model can have error saved for its field after a validation call. This
 * method "pretty print" the error (in HTML) as a view helper. It will wrap the
 * message with an HTML element stylized for error display.
 *
 * @param $model
 *   The model object
 * @param $field
 *   The model's field to check against error.
 *
 * @return
 *   A string (empty if there is no error on the given field for the model).
 */
function errors_for($model, $field) {
    if (is_null($model->errors($field)))
        return '';

    $html = '';
    foreach ($model->errors($field) as $msg)
        $html .= " $msg";
    return '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' . $html . '</div>';
}

/**
 * Cross-site request forgery token generator.
 *
 * see https://en.wikipedia.org/wiki/Cross-site_request_forgery
 *
 * @return
 *   A token as a string that is HTML / Javascript safe.
 */
function csrf_token()
{
    if (!session_active())
        return "";
    if (!array_key_exists('_no2_csrf_token', $_SESSION))
        $_SESSION['_no2_csrf_token'] = sprintf("csrf.%s", uuidv4());
    return $_SESSION['_no2_csrf_token'];
}

/**
 * verify a CSRF token.
 *
 * @param $token (string)
 *   The token to verify.
 *
 * @return
 *   TRUE on success, FALSE otherwise.
 */
function csrf_token_check($token)
{
    return hash_equals(csrf_token(), strval($token));
}

/**
 * CORS (Cross-Origin Resource Sharing) and Preflighted requests
 *
 * @param $allowed_origins
 *   allowed Access-Control-Allow-Origin header that we can send.
 *
 * @param $allow_credentials
 *   if true, we send Access-Control-Allow-Credentials.
 *
 * @return
 *   true if the request should stop, false otherwise.
 *
 * @see
 *   https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 */
function cross_origin_resource_sharing($allowed_origins = [], $allow_credentials = false)
{
    $headers = 0;
    $req_http_headers = array_change_key_case(getallheaders(), CASE_LOWER);

    if ($allow_credentials) {
        // send the Credentials Allow anyway (GET requests are not preflighted)
        header('Access-Control-Allow-Credentials: true');
    }

    if (array_key_exists('origin', $req_http_headers)) {
        $origin = $req_http_headers['origin'];
        // ensure to allow only pre-configured origins.
        if (in_array($origin, $allowed_origins)) {
            // this should be safe, according to the doc header() will prevent
            // injection, see http://us2.php.net/manual/en/function.header.php
            header("Access-Control-Allow-Origin: $origin");
            header('Vary: Origin', false /* don't replace previous Vary header */);
            $headers++;
        }
    }
    if (array_key_exists('access-control-request-method', $req_http_headers)) {
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
        $headers++;
    }
    if (array_key_exists('access-control-request-headers', $req_http_headers)) {
        // XXX: X-Requested-With is a jQuery idiom we tolerate.
        header('Access-Control-Allow-Headers: Accept, Content-Type, X-Requested-With');
        $headers++;
    }

    return ($headers > 0 && $_SERVER['REQUEST_METHOD'] === 'OPTIONS');
}

/**
 * format a DateTime in the configured datetime format for display.
 *
 * @param $dt
 *   The DateTime object to format.
 *
 * @return
 *   A string.
 */
function datetime_to_s($dt)
{
    $fmt = AppConfig::get('l10n.strftime_datetime_format');
    return strftime($fmt, $dt->getTimestamp());
}

/**
 * format a DateTime in the configured date format for display.
 *
 * @param $dt
 *   The DateTime object to format.
 *
 * @return
 *   A string.
 */
function date_to_s($dt)
{
    $fmt = AppConfig::get('l10n.strftime_date_format');
    return strftime($fmt, $dt->getTimestamp());
}

/**
 * Parse a string to DateTime.
 *
 * @param $s
 *   A string in the configured datetime format.
 *
 * @return
 *   A DateTime object or null on failure.
 */
function s_to_datetime($s)
{
    $ret    = null;
    $fmt    = AppConfig::get('l10n.strftime_datetime_format');
    $parsed = strptime($s, $fmt);
    if ($parsed) {
        $t = mktime(
            $parsed['tm_hour'],
            $parsed['tm_min'],
            $parsed['tm_sec'],
            $parsed['tm_mon'] + 1,
            $parsed['tm_mday'],
            $parsed['tm_year'] + 1900
        );
        $ret = new DateTime();
        $ret->setTimestamp($t);
    }
    return $ret;
}

/**
 * Parse a string to DateTime.
 *
 * @param $s
 *   A string in the configured date format.
 *
 * @return
 *   A DateTime object or null on failure.
 */
function s_to_date($s)
{
    $ret    = null;
    $fmt    = AppConfig::get('l10n.strftime_date_format');
    $parsed = strptime($s, $fmt);
    if ($parsed) {
        $t = mktime(
            $parsed['tm_hour'],
            $parsed['tm_min'],
            $parsed['tm_sec'],
            $parsed['tm_mon'] + 1,
            $parsed['tm_mday'],
            $parsed['tm_year'] + 1900
        );
        $ret = new DateTime();
        $ret->setTimestamp($t);
    }
    return $ret;
}
