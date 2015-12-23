<?php
/**
 * Handle HTTP errors like 404 Not Found. Unlike classic controller, this one
 * exist only to allow a view to render a nice error page. It doesn't do
 * anything but help the view.
 */
require_once(APPDIR . '/controllers/base_controller.class.php');

class ErrorController extends BaseController {

    /**
     * HTTP error status code.
     */
    private $status;

    /**
     * override ctor to avoid having to know what alias / action to use outside this class.
     *
     * @param $http_error_status_code
     *   The HTTP status code.
     */
    public function __construct($http_error_status_code) {
        parent::__construct('error', 'error', 'GET');
        $this->status = $http_error_status_code;
    }

    /**
     * a dummy action, to allow invoke() call.
     */
    protected function GET_error() {
        $this->view->html_title = 'Error ' . $this->status;
        // forward the status code to the view.
        return $this->status;
    }

    /**
     * will be called by ErrorView::render_error().
     */
    protected function render_error_page() {
        return $this->render_page();
    }

    /**
     * authorize everyone to see error pages.
     */
    protected function authorize($user, $action) {
        return TRUE;
    }

    /**
     * Always accept to render errors.
     */
    public function can_render_errors() {
        return TRUE;
    }

    /**
     * ErrorController use its own view that will honor render_error().
     */
    protected function view_class() {
        return 'ErrorView';
    }
}

/**
 * @class ErrorView
 *
 * ErrorController view class.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class ErrorView extends BaseView {
    /**
     * from here we're going back to render(), the ErrorController will give
     * the same template for 'error_page' than for 'page'. This will allow to
     * render exactly the same as 'page' without triggering the codepath that
     * called this method.
     */
    protected function render_error() {
        $this->render('error_page');
    }
}
