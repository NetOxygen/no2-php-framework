<?php

require_once dirname(__FILE__) . '/../../../no2/controller.class.php';

class No2_AbstractControllerTest extends PHPUnit_Framework_TestCase
{
    function test_ctor() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_respond_to() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_invoke() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_before_filter() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    function test_pre_render() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    // XXX: this has to be changed for something like new_view(), so the view 
    // ctor call can be overrided easily. At the moment it is done in the ctor 
    // with the class provded by view_class(), however more responsibility in 
    // this method means more control for subclasses.
    function test_view_class() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}
