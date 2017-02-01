<?php
/**
 * This class implement the view logic in the MVC pattern.
 *
 * It is not expected to write a lot of subclasses of No2_View. Most subclasses
 * will only override No2_View::render_error() Optionally No2_View::_render()
 * and a few other method could be overrided.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class No2_View
{

    /**
     * HTTP status code to return to the client.
     */
    private $__http_status;

    /**
     * getter fot the __http_status property.
     *
     * @return
     *   The status property of this object, a HTTP status code.
     */
    public function status()
    {
        return $this->__http_status;
    }

    /**
     * setter for the __http_status property.
     *
     * @param $status
     *   The new HTTP status code to return.
     *
     * @note
     *   Calling this method after render() has been called won't have any
     *   effect as the HTTP status header is the first thing sent back to the
     *   client.
     */
    public function set_status($status)
    {
        /* XXX should we ensure a proper HTTP status ? */
        $this->__http_status = $status;
    }

    /**
     * Location to redirect the client to.
     */
    protected $__redirect_location = '/';

    /**
     * Controller owning this object.
     */
    protected $controller;

    /**
     * array saved across redirections.
     *
     * @note
     *   The implementation use <code>$_SESSION['_no2_flash']</code>
     *   to temporarily save this property.
     */
    public $flash;

    /**
     * Create a new view.
     *
     * The status property will be set to No2_HTTP::OK as default status code.
     * If a flash was saved, it is loaded into this view and cleared from the
     * session.
     *
     * @param $controller
     *   The controller that called this view.
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
        $this->set_status(No2_HTTP::OK);
        $this->load_flash_from_session();
    }

    /**
     * explicitely load the flash from the session.
     */
    public function load_flash_from_session()
    {
        /*
         * initialize the flash in both this and the session if possible.
         */
        if (session_active() && array_key_exists('_no2_flash', $_SESSION)) {
            $this->flash = $_SESSION['_no2_flash'];
            unset($_SESSION['_no2_flash']);
        } else
            $this->flash = [];
    }

    /**
     * set the HTTP header's Status string.
     *
     * As a general PHP limitation, the application must hit this codepath
     * before any output is printed/echo'ed back to the client.
     */
    protected function set_http_header()
    {
        $http_status_string = No2_HTTP::header_status_string($this->status());
        if (!is_null($http_status_string))
            header($http_status_string);
    }

    /**
     * Render a zone.
     *
     * This method is the main method to generate output.
     *
     * @param $zone
     *   The zone to render. The default, `page', is the root zone for
     *   rendering. When the `page' zone is asked to be rendered, HTTP headers
     *   are set. render_error() or render_redirect() can be called
     *   depending on the __http_status property of this object.
     *
     * @see No2_AbstractController::pre_render(), render_error(),
     *   render_redirect()
     */
    public function render($zone = 'page')
    {
        No2_Logger::no2debug(get_class($this) . "::render: zone=$zone");
        if ($zone == 'page') {
            $this->set_http_header();

            // on error or redirect call the proper methods.
            if (No2_HTTP::is_error($this->status())) {
                $this->render_error();
                return;
            } else if (No2_HTTP::is_redirection($this->status())) {
                $this->render_redirect();
                return;
            }

        }

        /* "normal" render code. */
        $tpl_file = $this->controller->pre_render($zone);
        if (!is_null($tpl_file)) {
            No2_Logger::no2debug(get_class($this) . "::render: rendering file $tpl_file");
            $this->_render($tpl_file);
        }
    }

    /**
     * A very simple error page.
     *
     * This method is intended to be overrided by subclass to handle errors in
     * a more friendly way.
     */
    protected function render_error()
    {
        $http_status_string = No2_HTTP::header_status_string($this->status());
        if (!is_null($http_status_string)) {
            $message = preg_replace('/HTTP\/\d+\.\d+\s*/', '', $http_status_string);
        } else {
            $message = "An error occured.";
        }
        ?>
        <html>
            <head><title><?php print h($message); ?></title></head>
            <body>
                <h1>Error: <?php print h($message); ?></h1>
            </body>
        </html>
        <?php
    }

    /**
     * perform a redirection the the location property if this object.
     */
    protected function render_redirect()
    {
        No2_Logger::no2debug(get_class($this) . "::render_redirect: redirecting to {$this->__redirect_location}");
        header("Location: {$this->__redirect_location}");
    }

    /**
     * redirect to the given location.
     *
     *
     * This method is designed to be called from the controller.
     *
     * @param $location
     *   An URI string, the location to redirect to.
     *
     * @param $status
     *   The HTTP status code. It should be a redirect status code. The default
     *   is 303 See Other as this method is intended to be used by action
     *   methods modifying the database by POST requests.
     *   (see http://en.wikipedia.org/wiki/HTTP_303)
     *
     * @return
     *   The given $status, allowing "return $view->redirect_to(...)" from
     *   controllers.
     */
    public function redirect_to($location, $status=No2_HTTP::SEE_OTHER)
    {
        if (No2_HTTP::is_redirection($status))
            $this->set_status($status);

        $this->__redirect_location = $location;

        // prepare the flash for the next call.
        if (session_active())
            $_SESSION['_no2_flash'] = $this->flash;

        return $status;
    }

    /**
     * This method include a template file to render it.
     *
     * We need this method to include templates files with a clean variables
     * env. From the template only globals methods and functions will be
     * available, plus <code>$this</code> object. All variables set by th
     * templates will be local to this method call
     * (and then local to the template).
     *
     * @note
     *   a subclass can override this method in order to add variables that
     *   should be accessible from the templates files. If so, it should
     *   (obviously) not call <code>parent::render()</code> but rather include
     *   the template file directly.
     *
     * @param $tpl_file
     *   The template file to include.
     */
    protected function _render($tpl_file)
    {
        include $tpl_file;
    }
}
