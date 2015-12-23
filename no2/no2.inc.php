<?php
/**
 * @file no2.inc.php
 *
 * By requiring/including this file the no2 stuff should be initialized.
 *
 * @note
 *   No2 assume MCV, although it doesn't force it.
 *   No2 assume UTF-8.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */

/**
 * The root path of the No2 stuff.
 */
define('NO2DIR', dirname(__FILE__));

/**
 * the framework's version.
 *
 * http://semver.org/
 */
define('NO2VERSION', trim(file_get_contents(NO2DIR . '/version.txt')));

require_once(NO2DIR . '/help.inc.php');
require_once(NO2DIR . '/http.class.php');
require_once(NO2DIR . '/logger.class.php');

require_once(NO2DIR . '/database.class.php');
require_once(NO2DIR . '/sql_query.class.php');
require_once(NO2DIR . '/ability.class.php');
require_once(NO2DIR . '/model.class.php');

require_once(NO2DIR . '/controller.class.php');

require_once(NO2DIR . '/view.class.php');

require_once(NO2DIR . '/router.class.php');
