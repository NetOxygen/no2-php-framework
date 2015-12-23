<?php
/**
 * @file index.php
 *
 * Single entry point of this application.
 *
 * Ideally, it doesn't contains any logic and just call blindly the MVC stack
 * in order. In practice it is something hard to know early where a codepath
 * belongs, and here is a good place for a small bunch of code with a FIXME
 * note (it keeps disorder ordered).
 *
 * This also should be the only place to set global variables, we own the
 * global namespace. We set:
 *   - $router
 *   - $controller
 *   - $view
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
include(dirname(__FILE__) . '/../bootstrap.inc.php');

/*
 * CORS setup, needed if we provide API calls that could be requested by other
 * web applications through scripting.
 *
 * see https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 */
if (AppConfig::get('security.cors.enabled', false)) {
    $continue = cross_origin_resource_sharing(
        AppConfig::get('security.cors.allowed-origins', []),
        AppConfig::get('security.cors.allow-credentials', false)
    );
    if (!$continue)
        die();
    unset($continue);
}

/*
 * get a router to handle and generate URLs.
 */
require_once(APPDIR . '/router.class.php');
$router = new AppRouter();
$router->decode_url($_SERVER['REQUEST_URI']);

/*
 * Find the controller that registered for the requested URI. Note that here
 * $_REQUEST['controller'] stand for an alias and not a controller class, hence
 * the need of the router's logic.
 */
$controller = $router->find_route(
    isset($_REQUEST['controller']) ? $_REQUEST['controller'] : NULL,
    isset($_REQUEST['action'])     ? $_REQUEST['action']     : NULL,
    $_SERVER['REQUEST_METHOD']
);
if (is_null($controller)) {
    require_once(APPDIR . '/controllers/error.class.php');
    $controller = new ErrorController(No2_HTTP::NOT_FOUND);
}

/*
 * Execute the requested action in order to be able to render the ressource.
 */
invoke_it:
if (No2_Logger::$level >= No2_Logger::DEBUG) {
    $controller->invoke();
} else {
    try {
        $controller->invoke();
    } catch(Exception $_) {
        // Something Bad™ happened. This exception wasn't expected, so we
        // go to a failsafe codepath.
        No2_Logger::err(__FILE__ . ': ' . $_->getMessage());
        header('HTTP/1.1 500 Internal Server Error');
        ?>
            <html>
                <head><title>500 Internal Server Error</title></head>
                <body>
                    <h1>500 Internal Server Error</h1>
                    <p>Please contact the site administrator</p>
                </body>
            </html>
        <?php
        die(/* unhappily */);
    }
}
$view = $controller->view();

if (No2_HTTP::is_error($view->status()) && !$controller->can_render_errors()) {
    /*
     * The controller declined error handling, so we load the default error
     * controller to generate the response.
     */
    require_once(APPDIR . '/controllers/error.class.php');
    $controller = new ErrorController($view->status());
    unset($view);
    goto invoke_it;
}

/* from this point, $controller and $view are set and valid. */

/*
 * Here we know the status code, log the request and render the requested ressource.
 */
No2_Logger::info("{$_SERVER['REMOTE_ADDR']} - {$_SERVER['REQUEST_METHOD']} - {$_SERVER['REQUEST_URI']} - {$view->status()}");

/* kindly ask the view to render the response */
try {
    /*
     * Don't try to buffer the view's output using something like ob_start(),
     * it will OOM PHP if the response is moderately big.
     */
    $view->render();
    die(/* happily */);
} catch(Exception $e) {
    No2_Logger::err('view rendering exception: ' . $e->getMessage());
}
