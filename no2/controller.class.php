<?php
/**
 * Base controller class.
 *
 * This class implement the controller's logic in the MVC pattern. While
 * overriding all its method is an option, subclasses are expected in most of
 * the cases to do the following:
 * - override No2_AbstractController::before_filter()
 * - override No2_AbstractController::view_class()
 * - define magic protected POST_$action & GET_$action methods to implement
 *   actions.
 * - define magic protected render_$zone methods to help view rendering.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
abstract class No2_AbstractController
{
    /**
     * Saved name of the alias under which this controller is invoked.
     */
    protected $alias;

    /**
     * Saved name of the action invoked.
     */
    protected $action;

    /**
     * Saved name of the HTTP verb invoked.
     *
     * @note
     *   This could be easily found with <code>$_SERVER['HTTP_METHOD']</code>
     *   however having it provided by the caller and saved here allow more flexibility
     *   (for example for testing purpose).
     */
    protected $http_method;

    /**
     * The view used by this controller to do the rendering. It is initialized
     * on __construct(). This object will interact closely during the action
     * execution and rendering. Once the controller's action is done, the view
     * take the control to render the page, using this controller when needed.
     *
     * @see No2_AbstractController::view().
     */
    protected $view;

    /**
     * In this class this variable is only a proxy (reference) to
     * <code>$this->view->flash</code>. The goal is to make flash access more
     * friendly from the controller code and allow controller to override the
     * flash behaviour by using its own flash object.
     */
    protected $flash;

    /**
     * Create a new controller.
     *
     * If this controller cannot honor the parameters, it will throw an
     * exception.
     *
     * @param $alias
     *   The alias used to call this controller.
     *
     * @param $action
     *   The action name that this controller will be asked to perform.
     *
     * @param $http_method
     *   The HTTP method (verb) used.
     *
     */
    public function __construct($alias, $action, $http_method)
    {
        if (!$this->respond_to($action, $http_method)) {
            throw new Exception(get_class($this) . " can't respond to: " .
                "alias=$alias, action=$action, http_method=$http_method");
        }

        $this->alias       = $alias;
        $this->action      = $action;
        $this->http_method = $http_method;
        $view_class = $this->view_class();
        $this->view = new $view_class($this);
        $this->flash =& $this->view->flash;
    }

    /**
     * Test if the controller can be invoked for a given action.
     *
     *  In this implementation the method name should be like <code>GET_list</code>
     *  and protected if the action name is 'list' and the HTTP method is GET.
     *
     * @param $action
     *   The action name.
     *
     * @param $http_method
     *   The HTTP method (verb) used.
     *
     * @return
     *  If this controller is not able to perform the action, false is
     *  returned. Otherwise the method's name is returned. Note that since the
     *  method is protected, it can't be called from the outside.
     *
     * @see No2_AbstractController::invoke()
     */
    public function respond_to($action, $http_method)
    {
        $method = "{$http_method}_{$action}";
        if ($this->has_protected_method($method))
            return $method;
        else
            return false;
    }

    /**
     * Test if this controller has a protected method with a given name.
     *
     * @param $method_name
     *   the method's name.
     *
     * @return
     *   true if a method matching <code>$method_name</code> is defined and
     *   protected, false otherwise.
     */
    protected function has_protected_method($method_name)
    {
        try {
            $reflect = new ReflectionMethod(get_class($this), $method_name);
        } catch (ReflectionException $e) { // no method match $method_name
            return false;
        }
        return $reflect->isProtected();
    }

    /**
     * Invoke a controller action.
     *
     * This method is responsible to call the proper action method asked. It
     * will:
     * - call before_filter() to check if this controller allow the action in
     *   the current context
     * - call the action.
     * - Forward the status code returned by the action to the view.
     *
     * @return Nothing.
     */
    public function invoke()
    {
        $status = $this->before_filter();

        if (No2_HTTP::is_success($status)) {
            $method = $this->respond_to($this->action, $this->http_method);
            $status = $this->$method();
        }

        if (!is_null($status))
            $this->view->set_status($status);
    }

    /**
     * Method called before any action to check if the action can be called.
     *
     * This method is intended to be overrided in subclasses. When called,
     * <code>$this->action</code> and <code>$this->http_method</code> will be
     * defined. Depending on its return value, the action will be performed or
     * discarded (not called). A good use case of this method is to check
     * authorization. This method can also be used to setup some instance or
     * view variables.
     *
     * @return
     *   A HTTP status code. If the returned value is No2_HTTP::OK then it
     *   allows the action to be performed. All other value will prevent the
     *   action to be called and passed to the view.
     *
     * @note
     *   The implementation in this class always return No2_HTTP::OK, so
     *   calling parent::before_filter() from a subclass ensure a clean pass.
     */
    protected function before_filter()
    {
        return No2_HTTP::OK;
    }

    /**
     * This method is called by the view before rendering a zone.
     *
     * The view is asked by the template to render a zone. It first call its
     * controller pre_render() method with the zone to render. pre_render()
     * will setup views properties if needed (setup). To do the rendering it
     * can either output data or return a file (like a template file) that will
     * be included (or both). It can also control if the zone should be
     * rendered or not (for example based on authorization) and return a file
     * or not accordingly.
     *
     * This implementation will call the magic protected method render_$zone if
     * it exist. All subclasses are expected to define such method instead of
     * overriding pre_render().
     *
     * @param $zone
     *   The zone the view has been asked to render.
     *
     * @return
     *   a (template) file to be included or null if nothing has to be done by
     *   the view.
     */
    public function pre_render($zone)
    {
        $method = "render_{$zone}";
        $tpl = null;
        if ($this->has_protected_method($method)) {
            $tpl = $this->$method();
        }
        if (!is_null($tpl)) {
            No2_Logger::no2debug(get_class($this) . "::pre_render(zone=$zone): " .
                "sending template " . basename($tpl) . " back to view");
        }
        return $tpl;
    }

    /**
     * Declare if this controller's view can render an error HTTP status.
     *
     * If true is returned, the view's render() method will be called as if the
     * status was a success. If false is returned the application may use a
     * default error handling codepath.
     */
    public function can_render_errors()
    {
        return false;
    }

    /**
     * The view class used to create a view instance.
     *
     * This method allow per controller view class.
     *
     * @note
     *   If you override this method, make sure that the file where the view
     *   class is defined is required before returning.
     *
     * @return
     *   a No2_View class name.
     */
    protected function view_class()
    {
        return 'No2_View';
    }

    /**
     * accessor for <code>$this->view</code>.
     *
     * This accessor is needed because after the action has been performed, the
     * view should take the control flow for the rendering part.
     *
     * @return
     *   The controller's <code>$view</code> property.
     */
    public function view()
    {
        return $this->view;
    }
}
