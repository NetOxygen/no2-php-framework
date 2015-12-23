<?php
/**
 * @file bootstrap.inc.php
 *
 * Initialize the application (there is no link with the Twitter Boostrap stuff
 * though).
 *
 * NOTE:
 *   Be careful to avoid global environment pollution (in other word, do NOT
 *   set any variable).
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */

// define a constant for local path
define('TESTSDIR', dirname(__FILE__));
define('PROJECTDIR', dirname(__FILE__).'/../..');
define('APPDIR', PROJECTDIR . '/app');
define('WEBDIR', PROJECTDIR . '/web');

// self-explanatory
require_once(PROJECTDIR . '/compat/all.inc.php');

// load Composer stuff
//require_once(PROJECTDIR . '/vendor/autoload.php');

// initialize no2 framework.
require_once(PROJECTDIR . '/no2/no2.inc.php');


// get the config stuff
require_once(APPDIR . '/config.class.php');
AppConfig::parse(TESTSDIR . '/config/config.yml',
    array(
        '{{TESTSDIR}}'   => TESTSDIR,
        '{{APPDIR}}'     => APPDIR,
        '{{PROJECTDIR}}' => PROJECTDIR,
        '{{WEBDIR}}'     => WEBDIR,
    )
);

// load the application's test models.
require_once(dirname(__FILE__) . '/no2.unit.tests/models/user.class.php');

// load the application's helpers.
require_once(APPDIR . '/help.inc.php');


// set the timezone
date_default_timezone_set(AppConfig::get('l10n.default_timezone'));

// set the locale
setlocale(LC_ALL, AppConfig::get('l10n.default_locale'));

// start the logger
if (!No2_Logger::setup(AppConfig::get('logger'))) {
    error_log('unable to setup Logger');
}

// connect to the database.
No2_SQLQuery::setup(AppConfig::get('database'));

// try our best to hide the fact that we still use PHP in the 21th century.
if (function_exists('header_remove')) {
    header_remove('X-Powered-By'); // PHP 5.3+
} else {
    @ini_set('expose_php', 'off');
}
