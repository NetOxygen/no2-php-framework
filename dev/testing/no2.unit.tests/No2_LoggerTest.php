<?php

require_once dirname(__FILE__) . '/../../../no2/help.inc.php'; // for uuidv4()
require_once dirname(__FILE__) . '/../../../no2/logger.class.php';

// XXX: syslog logging is untested.
class No2_LoggerTest extends PHPUnit_Framework_TestCase
{
    static $LOGFILE_PATH = NULL;

    public function setUp() {
        if (is_null(static::$LOGFILE_PATH)) {
            static::$LOGFILE_PATH = '/tmp/no2-' . uuidv4() . '.log';
        }

        No2_Logger::setup(array(
            'name'  => 'PHPUnit',
            'level' => No2_Logger::DEBUG,
            'logfile_path'  => static::$LOGFILE_PATH,
        ));
    }

    public function tearDown() {
        unlink(static::$LOGFILE_PATH);
    }

    function test_output_format() {
        $date = preg_quote(date(DateTime::ISO8601), '/');
        $reqid = preg_quote(No2_Logger::$reqid, '/');
        $this->expectOutputRegex("/^\[PHPUnit {$date} {$reqid}\] DEBUG: test$/");
        No2_Logger::debug('test');
        print file_get_contents(static::$LOGFILE_PATH);
    }

    function test_logger_setup_level() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_emerg() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_alert() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_crit() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_err() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_warn() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_notice() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_info() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_debug() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_no2debug() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_debug_handle_varargs() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_debug_handle_null_gracefully() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_debug_uses_print_r_for_non_string_stuff() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
