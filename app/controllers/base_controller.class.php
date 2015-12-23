<?php
/**
 * Parent of all Controller. Used to add stuff specific to the application shared
 * by all controllers.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
require_once(APPDIR . '/views/base_view.class.php');

class BaseController extends No2_AbstractController {

    /**
     * can be overrided for controller that does not want the csrf check.
     *
     * @return
     *   true if this controller need the csrf protection, false otherwise.
     */
    protected function check_csrf()
    {
        return AppConfig::get('security.csrf', true);
    }

    /**
     * Add authorization check before calling any action
     *
     * @return
     *   a HTTP status code. This method add only authorization checks
     *   so it can return No2_HTTP::UNAUTHORIZED, No2_HTTP::FORBIDDEN or
     *   No2_HTTP::OK.
     */
    protected function before_filter()
    {
        // csrf check
        $csrf_methods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        if ($this->check_csrf() && in_array($this->http_method, $csrf_methods)) {
            $req_http_headers = array_change_key_case(getallheaders(), CASE_LOWER);
            if (array_key_exists('x-csrf-token', $req_http_headers))
                $token = $req_http_headers['x-csrf-token'];
            else if (array_key_exists('_csrf', $_REQUEST))
                $token = $_REQUEST['_csrf'];
            else
                $token = "";
            if (!csrf_token_check($token)) {
                No2_Logger::warn(sprintf('bad CSRF token: expected [%s] but got [%s]', csrf_token(), $token));
                return No2_HTTP::BAD_REQUEST;
            }
        }

        // authorization check
        if (!$this->authorize(current_user(), $this->action)) {
            return (current_user()->is_anonymous() ?
                No2_HTTP::UNAUTHORIZED : No2_HTTP::FORBIDDEN);
        }

        return parent::before_filter();
    }

    /**
     * Authorization verification.
     *
     * This method should be overrided in every controller to setup proper
     * authorization. This method should heavily rely on the Ability framework
     * to ensure that the user is authorized to perform the requested action.
     *
     * @param $user
     *   The user to authorize.
     *
     * @param $action
     *   The action requested. In addition <code>$_REQUEST</code> can contains
     *   additional info depending on the method.
     *
     * @return
     *   TRUE if the user is authorized, FALSE otherwise.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *   This method is only a stub allowing subclasses to parent::authorize().
     */
    protected function authorize($user, $action) {
        return FALSE;
    }

    /**
     * override the can_render_errors() method, we only want to render XHR
     * calls (via jQuery) and let the ErrorController handle "classic" browser
     * requests.
     */
    public function can_render_errors() {
        return (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }

    /**
     * Use the application's custom view.
     */
    protected function view_class() {
        return 'BaseView';
    }

    /**
     * allow each controller to override the page template.
     */
    protected function render_page() {
        $override = template("{$this->alias}/page");
        if (file_exists($override) && is_readable($override))
            return $override;
        return template('page');
    }

    /**
     * select a template per controller / action. By using the alias we allow
     * the same controller to have different template directories.
     */
    protected function render_content() {
        return template("{$this->alias}/{$this->action}");
    }

    /**
     * partial render.
     */
    protected function render_header() {
        return template('_header');
    }

    /**
     * partial render.
     */
    protected function render_messages() {
        return template('_messages');
    }

    /**
     * partial render.
     */
    protected function render_footer() {
        return template('_footer');
    }

    /**
     * partial render.
     */
    protected function render_debug() {
        if (No2_Logger::$level >= No2_Logger::DEBUG)
            No2_Logger::to_html('debugger');
    }
}
