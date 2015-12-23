<?php
/**
 * This class is used to register controller and find them. On registration,
 * controller must provide the alias to which they respond, and an optional
 * file to load where they're declared and defined.
 *
 * @note
 *   The current implementation is very simple. It could handle a parameter
 *   which is the whole URL like /?q=/admin/user/add (with possible rewrite)
 *   and use a regexp based mechanism to route to find the controller.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class No2_Router
{
    /**
     * map alias of controller to controller class.
     */
    protected $mapping = [];

    /**
     * Register a controller to respond to an alias.
     *
     * @param $alias
     *   alias under the controller is registered.
     *
     * @param $klass
     *   the controller Class
     *
     * @param $file
     *   the file where the controller is defined. It will be required before a
     *   controller will be created.
     */
    public function register($alias, $klass, $file = null)
    {
        if (!is_null($file) && (!is_file($file) || !is_readable($file))) {
            No2_Logger::err(get_class($this) . "::register: can't read controller file: $file");
            $file = null;
        }
        $this->mapping[$alias] = ['controller' => $klass, 'file' => $file];
    }

    /**
     * find the associated controller to $alias.
     *
     * @param $alias
     *   the alias to find a matching controller
     *
     * @param $action
     *   The action (method) to perform. It will be passed to the respond_to()
     *   method of the controller in order to check if it can handle it.
     *
     * @param $http_method
     *   The HTTP verb, usually GET or POST.
     *
     * @return
     *   a controller object that can handle $action. If there isn't any, null is
     *   returned.
     */
    public function find_route($alias, $action, $http_method)
    {
        if (!array_key_exists($alias, $this->mapping))
            return null;
        $target = $this->mapping[$alias];

        /* require the associated file if any */
        if (!is_null($target['file']))
            require_once($target['file']);

        // check if the controller responding to $alias can handle $action
        $klass = $target['controller'];
        try {
            $controller = new $klass($alias, $action, $http_method);
        } catch (Exception $e) {
            No2_Logger::warn(get_class($this) . '::find_route: ' .
                'exception in controller ctor: ' . $e->getMessage());
            return null;
        }

        return $controller;
    }

    /**
     * Get this router's hostname.
     *
     * @return
     *   the hostname to use to generate URLs.
     */
    public function hostname()
    {
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Get the base URL (hostname plus path) for this application.
     *
     * This method can be used to translate relative path to absolute path, for
     * exemple for css or javascript file location.
     *
     * @return
     *   a string with the full base URL, with a trailing slash `/'.
     */
    public function base_url()
    {
        $protocol = (empty($_SERVER['HTTPS']) ? 'http' : 'https');
        $base     = dirname($_SERVER['SCRIPT_NAME']);
        // add a trailing slash `/' if needed.
        if ($base !== '/')
            $base .= '/';
        $hostname = $this->hostname();
        $base_url = "{$protocol}://{$hostname}{$base}";
        return $base_url;
    }

    /**
     * used for static files (images, css, javascript etc.).
     *
     * This method allow to host assets files on a different
     * domain(s) (and/or sub-domain(s)), load balance etc. when
     * overrided.
     */
    public function assets_url($path)
    {
        return $this->base_url() . $path;
    }

    /**
     * Referer URL getter.
     *
     * Use this function to redirect the user to the referer URL.
     *
     * @return
     *   The referer URL.
     */
    public function back()
    {
        return $_SERVER['HTTP_REFERER'];
    }
}
