<?php

require_once dirname(__FILE__) . '/../../../no2/help.inc.php'; // for uuidv4()
require_once dirname(__FILE__) . '/../../../no2/logger.class.php';

// XXX: syslog logging is untested.
class No2_LoggerTest extends PHPUnit_Framework_TestCase
{
    static $LOGFILE_PATH = null; // see setUp()

    public function setUp()
    {
        if (is_null(static::$LOGFILE_PATH)) {
            static::$LOGFILE_PATH = sprintf('/tmp/no2-%s.log', uuidv4());
        }
    }

    public function tearDown()
    {
        if (@is_file(static::$LOGFILE_PATH))
            unlink(static::$LOGFILE_PATH);
    }

    /* helperzz */

    protected function full_logged_content()
    {
        return file_get_contents(static::$LOGFILE_PATH);
    }

    protected function nth_logged_line($n)
    {
        // NOTE: $n (the line number) is 1-indexed.
        $lines = file(static::$LOGFILE_PATH);
        if ($n < 1 || $n > count($lines)) {
            throw new InvalidArgumentException("$n: bad line index");
        }
        return $lines[$n - 1];
    }

    /* tests */

    function test_output_format()
    {
        $name = 'PHPUnit-Loggerzz';
        No2_Logger::setup([
            'name'         => $name,
            'logfile_path' => static::$LOGFILE_PATH,
        ]);
        No2_Logger::info('Winter is Coming');
        $expected = sprintf("[%s %s %s] INFO: Winter is Coming\n",
            $name, date('c'), No2_Logger::$reqid);
        $this->assertEquals($expected, $this->full_logged_content());
    }

    /* No2_Logger::level_to_string() tests */

    function test_level_to_string()
    {
        $this->assertEquals('EMERGENCY', No2_Logger::level_to_string(No2_Logger::EMERG));
        $this->assertEquals('ALERT',     No2_Logger::level_to_string(No2_Logger::ALERT));
        $this->assertEquals('CRITICAL',  No2_Logger::level_to_string(No2_Logger::CRIT));
        $this->assertEquals('ERROR',     No2_Logger::level_to_string(No2_Logger::ERR));
        $this->assertEquals('WARNING',   No2_Logger::level_to_string(No2_Logger::WARN));
        $this->assertEquals('NOTICE',    No2_Logger::level_to_string(No2_Logger::NOTICE));
        $this->assertEquals('INFO',      No2_Logger::level_to_string(No2_Logger::INFO));
        $this->assertEquals('DEBUG',     No2_Logger::level_to_string(No2_Logger::DEBUG));
        $this->assertEquals('No2/DEBUG', No2_Logger::level_to_string(No2_Logger::_NO2_DEBUG));
    }

    /* No2_Logger::string_to_level() tests */

    function test_string_to_level()
    {
        $this->assertEquals(No2_Logger::EMERG,      No2_Logger::string_to_level('EMERGENCY'));
        $this->assertEquals(No2_Logger::ALERT,      No2_Logger::string_to_level('ALERT'));
        $this->assertEquals(No2_Logger::CRIT,       No2_Logger::string_to_level('CRITICAL'));
        $this->assertEquals(No2_Logger::ERR,        No2_Logger::string_to_level('ERROR'));
        $this->assertEquals(No2_Logger::WARN,       No2_Logger::string_to_level('WARNING'));
        $this->assertEquals(No2_Logger::NOTICE,     No2_Logger::string_to_level('NOTICE'));
        $this->assertEquals(No2_Logger::INFO,       No2_Logger::string_to_level('INFO'));
        $this->assertEquals(No2_Logger::DEBUG,      No2_Logger::string_to_level('DEBUG'));
        $this->assertEquals(No2_Logger::_NO2_DEBUG, No2_Logger::string_to_level('No2/DEBUG'));
    }

    /* No2_Logger::setup() tests */

    function test_logger_setup_default_level()
    {
        $success = No2_Logger::setup([]);
        $this->assertTrue($success);
        $this->assertEquals(No2_Logger::INFO, No2_Logger::$level);
    }

    function test_logger_setup_bad_level()
    {
        $this->assertFalse($bad_string_level = No2_Logger::setup(['level' => 'LOL']));
        $this->assertFalse($short_level      = No2_Logger::setup(['level' => -1]));
        $this->assertFalse($big_level        = No2_Logger::setup(['level' => No2_Logger::LEVEL_MAX + 1]));
    }

    function test_logger_setup_levels()
    {
        for ($i = No2_Logger::EMERG; $i <= No2_Logger::LEVEL_MAX; $i++) {
            $level = $i;
            $this->assertTrue(No2_Logger::setup(['level' => $level]),
                "No2_Logger::setup i=$i");
            $this->assertEquals($level, No2_Logger::$level);
        }
    }

    function test_logger_setup_string_levels()
    {
        for ($i = No2_Logger::EMERG; $i <= No2_Logger::LEVEL_MAX; $i++) {
            $level = No2_Logger::level_to_string($i);
            $this->assertTrue(No2_Logger::setup(['level' => $level]),
                "No2_Logger::setup level=$level (i=$i)");
            $actual = No2_Logger::level_to_string(No2_Logger::$level);
            $this->assertEquals($level, $actual);
        }
    }

    /* EMERGENCY level tests */

    function test_logged_emerg_at_emerg_level()
    {
        No2_Logger::setup(['level' => No2_Logger::EMERG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::emerg('system is unusable');
        $this->assertRegExp('/\] EMERGENCY: system is unusable$/', $this->full_logged_content());
    }

    function test_silent_alert_at_emerg_level()
    {
        No2_Logger::setup(['level' => No2_Logger::EMERG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::alert('action must be taken immediately');
        $this->assertEquals('', $this->full_logged_content());
    }

    /* ALERT level tests */

    function test_logged_emerg_at_alert_level()
    {
        No2_Logger::setup(['level' => No2_Logger::ALERT, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::emerg('system is unusable');
        $this->assertRegExp('/\] EMERGENCY: system is unusable$/', $this->full_logged_content());
    }

    function test_logged_alert_at_alert_level()
    {
        No2_Logger::setup(['level' => No2_Logger::ALERT, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::alert('action must be taken immediately');
        $this->assertRegExp('/\] ALERT: action must be taken immediately$/', $this->full_logged_content());
    }

    function test_silent_crit_at_alert_level()
    {
        No2_Logger::setup(['level' => No2_Logger::ALERT, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::crit('critical conditions');
        $this->assertEquals('', $this->full_logged_content());
    }

    /* CRITICAL level tests */

    function test_logged_alert_at_crit_level()
    {
        No2_Logger::setup(['level' => No2_Logger::CRIT, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::alert('action must be taken immediately');
        $this->assertRegExp('/\] ALERT: action must be taken immediately$/', $this->full_logged_content());
    }

    function test_logged_crit_at_crit_level()
    {
        No2_Logger::setup(['level' => No2_Logger::CRIT, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::crit('critical conditions');
        $this->assertRegExp('/\] CRITICAL: critical conditions$/', $this->full_logged_content());
    }

    function test_silent_err_at_crit_level()
    {
        No2_Logger::setup(['level' => No2_Logger::CRIT, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::err('error conditions');
        $this->assertEquals('', $this->full_logged_content());
    }

    /* ERROR level tests */

    function test_logged_crit_at_err_level()
    {
        No2_Logger::setup(['level' => No2_Logger::ERR, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::crit('critical conditions');
        $this->assertRegExp('/\] CRITICAL: critical conditions$/', $this->full_logged_content());
    }

    function test_logged_err_at_err_level()
    {
        No2_Logger::setup(['level' => No2_Logger::ERR, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::err('error conditions');
        $this->assertRegExp('/\] ERROR: error conditions$/', $this->full_logged_content());
    }

    function test_silent_warn_at_err_level()
    {
        No2_Logger::setup(['level' => No2_Logger::ERR, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::warn('warning conditions');
        $this->assertEquals('', $this->full_logged_content());
    }

    /* WARNING level tests */

    function test_logged_err_at_warn_level()
    {
        No2_Logger::setup(['level' => No2_Logger::WARN, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::err('error conditions');
        $this->assertRegExp('/\] ERROR: error conditions$/', $this->full_logged_content());
    }

    function test_logged_warn_at_warn_level()
    {
        No2_Logger::setup(['level' => No2_Logger::WARN, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::warn('warning conditions');
        $this->assertRegExp('/\] WARNING: warning conditions$/', $this->full_logged_content());
    }

    function test_silent_notice_at_warn_level()
    {
        No2_Logger::setup(['level' => No2_Logger::WARN, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::notice('normal but significant condition');
        $this->assertEquals('', $this->full_logged_content());
    }

    /* NOTICE level tests */

    function test_logged_warn_at_notice_level()
    {
        No2_Logger::setup(['level' => No2_Logger::NOTICE, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::warn('warning conditions');
        $this->assertRegExp('/\] WARNING: warning conditions$/', $this->full_logged_content());
    }

    function test_logged_notice_at_notice_level()
    {
        No2_Logger::setup(['level' => No2_Logger::NOTICE, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::notice('normal but significant condition');
        $this->assertRegExp('/\] NOTICE: normal but significant condition$/', $this->full_logged_content());
    }

    function test_silent_info_at_notice_level()
    {
        No2_Logger::setup(['level' => No2_Logger::NOTICE, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::info('informational messages');
        $this->assertEquals('', $this->full_logged_content());
    }

    /* INFO level tests */

    function test_logged_notice_at_info_level()
    {
        No2_Logger::setup(['level' => No2_Logger::INFO, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::notice('normal but significant condition');
        $this->assertRegExp('/\] NOTICE: normal but significant condition$/', $this->full_logged_content());
    }

    function test_logged_info_at_info_level()
    {
        No2_Logger::setup(['level' => No2_Logger::INFO, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::info('informational messages');
        $this->assertRegExp('/\] INFO: informational messages$/', $this->full_logged_content());
    }

    function test_silent_debug_at_info_level()
    {
        No2_Logger::setup(['level' => No2_Logger::INFO, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::debug('debug messages');
        $this->assertEquals('', $this->full_logged_content());
    }

    /* DEBUG level tests */

    function test_logged_info_at_debug_level()
    {
        No2_Logger::setup(['level' => No2_Logger::DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::info('informational messages');
        $this->assertRegExp('/\] INFO: informational messages$/', $this->full_logged_content());
    }

    function test_logged_debug_at_debug_level()
    {
        No2_Logger::setup(['level' => No2_Logger::DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::debug('debug messages');
        $this->assertRegExp('/\] DEBUG: debug messages$/', $this->full_logged_content());
    }

    function test_silent_no2debug_at_debug_level()
    {
        No2_Logger::setup(['level' => No2_Logger::DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::no2debug('Used internally by No2 files');
        $this->assertEquals('', $this->full_logged_content());
    }

    function test_debug_handle_varargs()
    {
        No2_Logger::setup(['level' => No2_Logger::DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::debug('pim', 'pam', 'poum');
        $this->assertRegExp('/\] DEBUG: pim$/',  $this->nth_logged_line(1));
        $this->assertRegExp('/\] DEBUG: pam$/',  $this->nth_logged_line(2));
        $this->assertRegExp('/\] DEBUG: poum$/', $this->nth_logged_line(3));
    }

    function test_debug_handle_null_gracefully()
    {
        No2_Logger::setup(['level' => No2_Logger::DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::debug(null);
        $this->assertRegExp('/\] DEBUG: NULL$/',  $this->full_logged_content());
    }

    function test_debug_uses_print_r_for_non_string_stuff()
    {
        No2_Logger::setup(['level' => No2_Logger::DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        $sample   = (object)['PHP' => 'should', 'die' => 'in', 'a' => 'fire'];
        $expected = '/\] DEBUG: ' . preg_quote(print_r($sample, true), '/') . '\Z/m';
        No2_Logger::debug($sample);
        $this->assertRegExp($expected,  $this->full_logged_content());
    }

    /* No2/DEBUG level tests */

    function test_logged_debug_at_no2debug_level()
    {
        No2_Logger::setup(['level' => No2_Logger::_NO2_DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::debug('debug messages');
        $this->assertRegExp('/\] DEBUG: debug messages$/', $this->full_logged_content());
    }

    function test_logged_no2debug_at_no2debug_level()
    {
        No2_Logger::setup(['level' => No2_Logger::_NO2_DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::no2debug('Used internally by No2 files');
        $this->assertRegExp('/\] No2\/DEBUG: Used internally by No2 files$/', $this->full_logged_content());
    }

    function test_no2debug_handle_varargs()
    {
        No2_Logger::setup(['level' => No2_Logger::_NO2_DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::no2debug('pim', 'pam', 'poum');
        $this->assertRegExp('/\] No2\/DEBUG: pim$/',  $this->nth_logged_line(1));
        $this->assertRegExp('/\] No2\/DEBUG: pam$/',  $this->nth_logged_line(2));
        $this->assertRegExp('/\] No2\/DEBUG: poum$/', $this->nth_logged_line(3));
    }

    function test_no2debug_handle_null_gracefully()
    {
        No2_Logger::setup(['level' => No2_Logger::_NO2_DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        No2_Logger::no2debug(null);
        $this->assertRegExp('/\] No2\/DEBUG: NULL$/',  $this->full_logged_content());
    }

    function test_no2debug_uses_print_r_for_non_string_stuff()
    {
        No2_Logger::setup(['level' => No2_Logger::_NO2_DEBUG, 'logfile_path' => static::$LOGFILE_PATH]);
        $sample   = (object)['PHP' => 'should', 'die' => 'in', 'a' => 'fire'];
        $expected = '/\] No2\/DEBUG: ' . preg_quote(print_r($sample, true), '/') . '\Z/m';
        No2_Logger::no2debug($sample);
        $this->assertRegExp($expected,  $this->full_logged_content());
    }
}
