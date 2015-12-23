<?php

require_once dirname(__FILE__) . '/../../../no2/http.class.php';

class No2_HTTPTest extends PHPUnit_Framework_TestCase
{
    function test_is_success() {
        $this->assertFalse(No2_HTTP::is_success(199)); // before inf limit
        $this->assertTrue(No2_HTTP::is_success(200));  // inferior limit
        $this->assertTrue(No2_HTTP::is_success(203));  // mid.
        $this->assertTrue(No2_HTTP::is_success(206));  // superior limit
        $this->assertFalse(No2_HTTP::is_success(300)); // after sup limit
    }

    function test_is_redirection() {
        $this->assertFalse(No2_HTTP::is_redirection(299)); // before inf limit
        $this->assertTrue(No2_HTTP::is_redirection(301));  // inferior limit
        $this->assertTrue(No2_HTTP::is_redirection(304));  // mid.
        $this->assertTrue(No2_HTTP::is_redirection(308));  // superior limit
        $this->assertFalse(No2_HTTP::is_redirection(400)); // after sup limit
    }

    function test_is_error() {
        $this->assertFalse(No2_HTTP::is_error(399)); // before inf limit
        $this->assertTrue(No2_HTTP::is_error(400));  // inferior limit
        $this->assertTrue(No2_HTTP::is_error(431));  // mid.
        $this->assertTrue(No2_HTTP::is_error(500));  // mid.
        $this->assertTrue(No2_HTTP::is_error(503));  // superior limit
        $this->assertFalse(No2_HTTP::is_error(600)); // after sup limit
    }

    function test_header_status_string() {
        $s = No2_HTTP::header_status_string(200);
        $this->assertInternalType('string', $s);
        $this->assertNotEmpty($s);
    }

    function test_header_status_string_http_version() {
        $this->assertRegExp("/^HTTP\/1\.1/", No2_HTTP::header_status_string(200));
    }

    function test_header_status_string_status_code() {
        $this->assertRegExp("/ 200 /", No2_HTTP::header_status_string(200));
    }

    function test_header_status_string_status_message() {
        $this->assertRegExp("/ OK$/", No2_HTTP::header_status_string(200));
    }

    function test_header_status_string_validity() {
        $this->assertEquals("HTTP/1.1 200 OK", No2_HTTP::header_status_string(200));
    }

    function test_header_status_string_works_with_many_status() {
        $this->assertEquals("HTTP/1.1 200 OK", No2_HTTP::header_status_string(200));
        $this->assertEquals("HTTP/1.1 303 See Other", No2_HTTP::header_status_string(303));
        $this->assertEquals("HTTP/1.1 403 Forbidden", No2_HTTP::header_status_string(403));
        $this->assertEquals("HTTP/1.1 404 Not Found", No2_HTTP::header_status_string(404));
        $this->assertEquals("HTTP/1.1 500 Internal Server Error", No2_HTTP::header_status_string(500));
    }
}
