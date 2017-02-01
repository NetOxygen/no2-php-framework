<?php

require_once dirname(__FILE__) . '/../../../no2/help.inc.php';

class No2_helpTest extends PHPUnit_Framework_TestCase
{
    public function test_http_host()
    {
        $canary = uuidv4();
        $_SERVER['HTTP_HOST'] = $canary; // ugly env setup.
        $this->assertEquals($canary, http_host());
    }

    public function test_h()
    {
        $unsafe   = "& and \" and ' and < and >";
        $expected = "&amp; and &quot; and &apos; and &lt; and &gt;";
        $this->assertEquals($expected, h($unsafe));
    }

    public function test_sane_json_encode()
    {
        if (PHP_VERSION_ID < 50606) {
            $phpversion = phpversion();
            $this->markTestSkipped("json_encode() is buggy under your PHP version ($phpversion)");
        } else {
            $this->assertTrue(10.0 === json_decode(sane_json_encode(10.0)));
        }
    }

    public function test_html_json_encode()
    {
        $unsafe   = "& and \" and ' and < and >";
        $expected = '"\u0026 and \u0022 and \u0027 and \u003C and \u003E"';
        $this->assertEquals($expected, html_json_encode($unsafe));
    }

    public function test_truemod()
    {
        // show a phpmod example as proof of concept.
        $phpmod = function ($num, $mod) { return $num % $mod; };
        $this->assertEquals(-1, $phpmod($phpmod(2, 2) - 1, 2));
        $this->assertEquals(+1, truemod(truemod(2, 2) - 1, 2));
    }

    public function test_is_uuidv4()
    {
        $this->assertFalse(is_uuidv4($uuidv1 = '63cbe654-8177-11e6-9b82-002522ee026d'));
        $this->assertFalse(is_uuidv4($uuidv3 = '643dc989-58e9-3e72-9fd5-e3b4a09b62e9'));
        $this->assertTrue( is_uuidv4($uuidv4 = 'f9f957ce-7265-4263-9923-dc0fbdd49ef9'));
        $this->assertFalse(is_uuidv4($uuidv5 = 'c8333619-8220-5b21-b5d9-2bd56713675a'));
    }

    public function test_iso8601_to_datetime()
    {
        $ts = '2016-09-19T14:32:45+02:00';
        $dt = iso8601_to_datetime($ts);

        $this->assertInstanceOf('DateTime', $dt);
        // Using DateTime::ATOM and not DateTime::ISO8601 is correct here,
        // see https://secure.php.net/manual/en/class.datetime.php#datetime.constants.iso8601
        $this->assertEquals($ts, $dt->format(DateTime::ATOM));
    }

    public function test_datetime_to_iso8601()
    {
        $dt = new DateTime();
        $dt->setDate(2016, 9, 19);
        $dt->setTime(14, 32, 45);
        // Europe/Zurich is CEST in september and CEST is UTC+02:00
        $dt->setTimeZone(new DateTimeZone('Europe/Zurich'));

        $ts = datetime_to_iso8601($dt);
        $this->assertEquals('2016-09-19T14:32:45+02:00', $ts);
    }

}
