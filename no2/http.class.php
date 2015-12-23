<?php
/**
 * This class is mostly used to store HTTP status code.
 *
 * @see http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class No2_HTTP
{
    /* if you add here a HTTP status code, don't forget to add it to header_status_string()
        as well. */

    /* 2xx Success */
    const OK              = 200; /**< HTTP/1.1 OK */
    const CREATED         = 201; /**< HTTP/1.1 Created */
    const ACCEPTED        = 202; /**< HTTP/1.1 Accepted */
    const NON_AUTH_INFO   = 203; /**< HTTP/1.1 Non-Authoritative Information */
    const NO_CONTENT      = 204; /**< HTTP/1.1 No Content */
    const RESET_CONTENT   = 205; /**< HTTP/1.1 Reset Content */
    const PARTIAL_CONTENT = 206; /**< HTTP/1.1 Partial Content */

    /* 3xx Redirection */
    const MOVED_PERM        = 301; /**< HTTP/1.1 Moved Permanently */
    const FOUND             = 302; /**< HTTP/1.1 Found */
    const SEE_OTHER         = 303; /**< HTTP/1.1 See Other */
    const NOT_MODIFIED      = 304; /**< HTTP/1.1 Not Modified */
    const USE_PROXY         = 305; /**< HTTP/1.1 Use Proxy */
    const SWITCH_PROXY      = 306; /**< HTTP/1.1 Switch Proxy */
    const TEMP_REDIRECT     = 307; /**< HTTP/1.1 Temporary Redirect */
    const RESUME_INCOMPLETE = 308; /**< HTTP/1.1 Resume Incomplete */

    /* 4xx Client Error */
    const BAD_REQUEST         = 400; /**< HTTP/1.1 Bad Request */
    const UNAUTHORIZED        = 401; /**< HTTP/1.1 Unauthorized */
    const PAYMENT_REQUIRED    = 402; /**< HTTP/1.1 Payment Required */
    const FORBIDDEN           = 403; /**< HTTP/1.1 Forbidden */
    const NOT_FOUND           = 404; /**< HTTP/1.1 Not Found */
    const METHOD_NOT_ALLOWED  = 405; /**< HTTP/1.1 Method Not Allowed */
    const NOT_ACCEPTABLE      = 406; /**< HTTP/1.1 Not Acceptable */
    const PROXY_AUTH_REQUIRED = 407; /**< HTTP/1.1 Proxy Authentication Required */
    const REQUEST_TIMEOUT     = 408; /**< HTTP/1.1 Request Timeout */
    const CONFLICT            = 409; /**< HTTP/1.1 Conflict */
    const GONE                = 410; /**< HTTP/1.1 Gone */
    const LENGTH_REQUIRED     = 411; /**< HTTP/1.1 Length Required */
    const PRECONDITION_FAILED = 412; /**< HTTP/1.1 Precondition Failed */
    const REQUEST_ENTITY_TOO_LARGE  = 413; /**< HTTP/1.1 Request Entity Too Large */
    const REQUEST_URI_TOO_LONG      = 414; /**< HTTP/1.1 Request-URI Too Long */
    const UNSUPPORTED_MEDIA_TYPE    = 415; /**< HTTP/1.1 Unsupported Media Type */
    const REQ_RANGE_NOT_SATISFIABLE = 416; /**< HTTP/1.1 Requested Range Not Satisfiable */
    const EXPECTATION_FAILED        = 417; /**< HTTP/1.1 Expectation Failed */
    const I_M_A_TEAPOT              = 418; /**< HTTP/1.1 I'm a teapot :) */
    const PRECONDITION_REQUIRED     = 428; /**< HTTP/1.1 Precondition Required */
    const TOO_MANY_REQUESTS         = 429; /**< HTTP/1.1 Too Many Requests */
    const REQUEST_HEADER_FIELDS_TOO_LARGE = 431; /**< HTTP/1.1 Request Header Fields Too Large */

    /* 5xx Server Error */
    const INTERNAL_SERVER_ERROR = 500; /**< HTTP/1.1 Internal Server Error */
    const NOT_IMPLEMENTED       = 501; /**< HTTP/1.1 Not Implemented */
    const BAD_GATEWAY           = 502; /**< HTTP/1.1 Bad Gateway */
    const SERVICE_UNAVAILABLE   = 503; /**< HTTP/1.1 Service Unavailable */
    const GATEWAY_TIMEOUT       = 504; /**< HTTP/1.1 Gateway Timeout */
    const HTTP_VERSION_NOT_SUPPORTED = 505; /**< HTTP/1.1 HTTP Version Not Supported */


    /**
     * Test if a status code is a success.
     *
     * @param $status
     *   the status code to test
     *
     * @return
     *   true if it match a 2xx Sucess status code, false otherwise.
     */
    public static function is_success($status)
    {
        return (intval($status) >= 200 && intval($status) < 300);
    }

    /**
     * Test if a status code is a redirection.
     *
     * @param $status
     *   the status code to test
     *
     * @return
     *   true if it match a 3xx Redirection status code, false otherwise.
     */
    public static function is_redirection($status)
    {
        return (intval($status) >= 300 && intval($status) < 400);
    }

    /**
     * Test if a status code is an error (client or server).
     *
     * @param $status
     *   the status code to test
     *
     * @return
     *   true if it match a 4xx Client Error or 5xx Server Error status code,
     *   false otherwise.
     */
    public static function is_error($status)
    {
        return (intval($status) >= 400 && intval($status) < 600);
    }

    /**
     * return a string that can be used as line for a header() call for the given status.
     * @see http://www.faqs.org/rfcs/rfc2616.html
     *
     * @param $status_code
     *   The HTTP status code.
     *
     * @return
     *   A string with a valid HTTP 1.1 Status line, or null if this module
     *   doesn't support this error code (invalid or unsupported).
     */
    public static function header_status_string($status_code)
    {
        static $reason_phrases = [
            No2_HTTP::OK              => "OK",
            No2_HTTP::CREATED         => "Created",
            No2_HTTP::ACCEPTED        => "Accepted",
            No2_HTTP::NON_AUTH_INFO   => "Non-Authoritative Information",
            No2_HTTP::NO_CONTENT      => "No Content",
            No2_HTTP::RESET_CONTENT   => "Reset Content",
            No2_HTTP::PARTIAL_CONTENT => "Partial Content",
            /* 3xx Redirection */
            No2_HTTP::MOVED_PERM        => "Moved Permanently",
            No2_HTTP::FOUND             => "Found",
            No2_HTTP::SEE_OTHER         => "See Other",
            No2_HTTP::NOT_MODIFIED      => "Not Modified",
            No2_HTTP::USE_PROXY         => "Use Proxy",
            No2_HTTP::SWITCH_PROXY      => "Switch Proxy",
            No2_HTTP::TEMP_REDIRECT     => "Temporary Redirect",
            No2_HTTP::RESUME_INCOMPLETE => "Resume Incomplete",
            /* 4xx Client Error */
            No2_HTTP::BAD_REQUEST               => "Bad Request",
            No2_HTTP::UNAUTHORIZED              => "Unauthorized",
            No2_HTTP::PAYMENT_REQUIRED          => "Payment Required",
            No2_HTTP::FORBIDDEN                 => "Forbidden",
            No2_HTTP::NOT_FOUND                 => "Not Found",
            No2_HTTP::METHOD_NOT_ALLOWED        => "Method Not Allowed",
            No2_HTTP::NOT_ACCEPTABLE            => "Not Acceptable",
            No2_HTTP::PROXY_AUTH_REQUIRED       => "Proxy Authentication Required",
            No2_HTTP::REQUEST_TIMEOUT           => "Request Timeout",
            No2_HTTP::CONFLICT                  => "Conflict",
            No2_HTTP::GONE                      => "Gone",
            No2_HTTP::LENGTH_REQUIRED           => "Length Required",
            No2_HTTP::PRECONDITION_FAILED       => "Precondition Failed",
            No2_HTTP::REQUEST_ENTITY_TOO_LARGE  => "Request Entity Too Large",
            No2_HTTP::REQUEST_URI_TOO_LONG      => "Request-URI Too Long",
            No2_HTTP::UNSUPPORTED_MEDIA_TYPE    => "Unsupported Media Type",
            No2_HTTP::REQ_RANGE_NOT_SATISFIABLE => "Requested Range Not Satisfiable",
            No2_HTTP::EXPECTATION_FAILED        => "Expectation Failed",
            No2_HTTP::I_M_A_TEAPOT              => "I'm a teapot",
            No2_HTTP::PRECONDITION_REQUIRED     => "Precondition Required",
            No2_HTTP::TOO_MANY_REQUESTS         => "Too Many Requests",
            No2_HTTP::REQUEST_HEADER_FIELDS_TOO_LARGE => "Request Header Fields Too Large",
                /* 5xx Server Error */
            No2_HTTP::INTERNAL_SERVER_ERROR => "Internal Server Error",
            No2_HTTP::NOT_IMPLEMENTED       => "Not Implemented",
            No2_HTTP::BAD_GATEWAY           => "Bad Gateway",
            No2_HTTP::SERVICE_UNAVAILABLE   => "Service Unavailable",
            No2_HTTP::GATEWAY_TIMEOUT       => "Gateway Timeout",
            No2_HTTP::HTTP_VERSION_NOT_SUPPORTED => "HTTP Version Not Supported",
        ];

        if (!array_key_exists($status_code, $reason_phrases))
            return null;

        $http_version  = 'HTTP/1.1';
        $reason_phrase = $reason_phrases[$status_code];
        $SP = ' ';

        // see http://www.faqs.org/rfcs/rfc2616.html § 6.1
        return $http_version . $SP . $status_code . $SP . $reason_phrase;
    }
}
