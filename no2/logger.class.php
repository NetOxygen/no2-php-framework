<?php
/**
 * Log errors messages with configurable levels.
 *
 * Before using any logging method be sure to call No2_Logger::setup().
 *
 * This implementation was inspired by KLogger:
 *   https://github.com/katzgrau/KLogger/blob/master/src/KLogger.php
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *   Although this class has a lot of method, they're all just syntaxic sugar
 *   and without complexity.
 */
class No2_Logger
{
    /*
     * Error severity, from low to high, taken from The Syslog Protocol.
     *
     * @see https://tools.ietf.org/html/rfc5424
     */
    const EMERG  = 0; /**< Emergency     : system is unusable               */
    const ALERT  = 1; /**< Alert         : action must be taken immediately */
    const CRIT   = 2; /**< Critical      : critical conditions              */
    const ERR    = 3; /**< Error         : error conditions                 */
    const WARN   = 4; /**< Warning       : warning conditions               */
    const NOTICE = 5; /**< Notice        : normal but significant condition */
    const INFO   = 6; /**< Informational : informational messages           */
    const DEBUG  = 7; /**< Debug         : debug messages                   */

    const _NO2_DEBUG = 8; /**< No2 Debug: Used internally by No2 files */
    const LEVEL_MAX = No2_Logger::_NO2_DEBUG;

    /*
     * used by level_to_string() and string_to_level().
     */
    protected static $__level_to_string = [
        self::EMERG  => 'EMERGENCY',
        self::ALERT  => 'ALERT',
        self::CRIT   => 'CRITICAL',
        self::ERR    => 'ERROR',
        self::WARN   => 'WARNING',
        self::NOTICE => 'NOTICE',
        self::INFO   => 'INFO',
        self::DEBUG  => 'DEBUG',
        self::_NO2_DEBUG => 'No2/DEBUG',
    ];

    /**
     * message sent higher than this level won't be logged. It default to
     * (-1) so all messages are ignored until No2_Logger::setup() has been
     * called.
     */
    public static $level = -1;

    /*
     * this logger's name, usually matching the application's name.
     */
    public static $name = '';

    /*
     * the request id, useful for debugging.
     */
    public static $reqid = '';

    /**
     * array storring the logged messages.
     */
    public static $messages = [];

    /**
     * File descriptor of the log file.
     */
    protected static $fd = null;

    /**
     * true if we need to use syslog, false otherwise.
     */
    protected static $syslog = false;

    /**
     * return the string representation of a given integer level.
     */
    public static function level_to_string($level)
    {
        if (array_key_exists($level, static::$__level_to_string))
            return static::$__level_to_string[$level];
        else
            return '?';
    }

    /**
     * return the integer representation of a given string level.
     */
    public static function string_to_level($str)
    {
        $string_to_level = array_flip(static::$__level_to_string);
        if (array_key_exists($str, $string_to_level))
            return $string_to_level[$str];
        else
            return -1;
    }

    /**
     * This method should be called before any attempt to log a message.
     *
     * <b>Example</b>
     * @code
     *   No2_Logger::setup([
     *     'name'  => 'My App.',
     *     'level' => No2_Logger::WARN,
     *     'logfile_path' => '/var/log/webapp.log'
     *   ]);
     * @endcode
     *
     * @param $params
     *   A associative array with the following keys:
     *   - name (optional, default: '')
     *       the name of the application
     *   - level (optional, default: INFO)
     *       the logging level. Messages sent to No2_Logger above this value
     *       will silently be ignored. It can be an integer (using this class
     *       constants) or a string (that will be passed to string_to_level()).
     *   - syslog_facility (optional, default: LOG_LOCAL0)
     *       set to a syslog facility if syslog logging is desired. Use for
     *       example LOG_LOCAL0.
     *   - logfile_path (optional)
     *       Path to a writable log file (will be opened in append mode).
     *
     * @return
     *   false on error, true otherwise.
     */
    public static function setup($params)
    {
        $level = (array_key_exists('level', $params) ? $params['level'] : self::INFO);
        if (is_string($level))
            $level = static::string_to_level($level);
        if (!is_numeric($level) || $level < self::EMERG || $level > self::_NO2_DEBUG)
            return false;

        $name = (array_key_exists('name', $params) ? $params['name'] : '');

        $syslog = false;
        if (array_key_exists('syslog_facility', $params)) {
            $facility = $params['facility'];
            if (!openlog($name, LOG_ODELAY, $facility))
                return false;
            static::$syslog = true;
        }

        $fd = null;
        if (array_key_exists('logfile_path', $params)) {
            $fd = fopen($params['logfile_path'], 'a');
            if (!$fd)
                return false;
            static::$fd = $fd;
        }

        /*
         * this ensure that either the logger is fully setup, or no config
         * values has changed since the last call.
         */

        static::$level  = $level;
        static::$name   = $name;
        static::$fd     = $fd;
        static::$syslog = $syslog;
        static::$reqid  = uuidv4();

        return true;
    }

    /**
     * log message with the Emergency (system is unusable) level.
     *
     * @param $message
     *   the message to log.
     */
    public static function emerg($message)
    {
        static::_log(self::EMERG, $message);
    }

    /**
     * log message with the Alert (action must be taken immediately) level.
     *
     * @param $message
     *   the message to log.
     */
    public static function alert($message)
    {
        static::_log(self::ALERT, $message);
    }

    /**
     * log message with the Critical (critical conditions) level.
     *
     * @param $message
     *   the message to log.
     */
    public static function crit($message)
    {
        static::_log(self::CRIT, $message);
    }

    /**
     * log message with the Error (error conditions) level.
     *
     * @param $message
     *   the message to log.
     */
    public static function err($message)
    {
        static::_log(self::ERR, $message);
    }

    /**
     * log message with the Warning (warning conditions) level.
     *
     * @param $message
     *   the message to log.
     */
    public static function warn($message)
    {
        static::_log(self::WARN, $message);
    }

    /**
     * log message with the Notice (normal but significant condition) level.
     *
     * @param $message
     *   the message to log.
     */
    public static function notice($message)
    {
        static::_log(self::NOTICE, $message);
    }

    /**
     * log message with the Informational (informational messages) level.
     *
     * @param $message
     *   the message to log.
     */
    public static function info($message)
    {
        static::_log(self::INFO, $message);
    }

    /**
     * log message with the Debug (debug messages) level.
     *
     * If $message is not a string, it will be passed to print_r() before
     * logging.
     *
     * @param $message
     *   the message to log.
     *
     * @see http://php.net/manual/en/function.print-r.php
     */
    public static function debug()
    {
        foreach (func_get_args() as $message) {
            if (is_null($message))
                $message = 'NULL';
            if (!is_string($message))
                $message = print_r($message, true);
            static::_log(self::DEBUG, $message);
        }
    }

    /**
     * log message with the NO2 Debug (debug messages) level.
     * This method is used by no2/ internally.
     *
     * If $message is not a string, it will be passed to print_r() before
     * logging.
     *
     * @param $message
     *   the message to log.
     *
     * @see http://php.net/manual/en/function.print-r.php
     */
    public static function no2debug()
    {
        foreach (func_get_args() as $message) {
            if (is_null($message))
                $message = 'NULL';
            if (!is_string($message))
                $message = print_r($message, true);
            static::_log(self::_NO2_DEBUG, $message);
        }
    }

    /**
     * General logging method. Actually write the message line to the
     * file.
     *
     * @param $level
     *   the level of error.
     *
     * @param $message
     *   the message to log.
     */
    protected static function _log($level, &$message)
    {
        if ($level < 0)
            return;

        if ($level <= static::$level) {
            $message = sprintf("[%s %s %s] %s: %s",
                static::$name,
                date('c'),
                static::$reqid,
                static::level_to_string($level),
                $message
            );
            static::$messages[] = $message;
            if (static::$syslog) {
                syslog($level, $message);
            }
            if (!is_null(static::$fd)) {
                fwrite(static::$fd, $message . "\n");
            }
        }
    }

    /**
     * print all the logged messages so far.
     *
     * @param $id
     *   A string that will be used as HTML DOM id for the div containing the
     *   messages.
     */
    public static function to_html($id)
    {
        if (!empty(static::$messages)): ?>
            <div id="<?php print htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>">
                <ul>
                    <?php foreach (static::$messages as $message): ?>
                        <li><pre><?php print htmlspecialchars($message, ENT_NOQUOTES | ENT_HTML5, 'UTF-8'); ?></pre></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif;
    }
}
