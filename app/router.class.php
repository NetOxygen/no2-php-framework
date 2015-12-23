<?php
/**
 * Custom Router.
 *
 * We add a lot of route helper to generate URL.
 *
 * FIXME: it badly need a $_REQUEST proxy.
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *   Although this class has a lot of method, they're all just syntaxic sugar
 *   and without complexity.
 */

class AppRouter extends No2_Router
{
    /**
     * register the controllers for routing.
     */
    public function __construct()
    {
        $this->register('user', 'UserController', APPDIR . '/controllers/user.class.php');
    }

    /**
     * overrided to set default values for $alias / $action.
     */
    public function find_route($alias, $action, $http_method)
    {
        if (empty($alias) && empty($action)) {
            $alias  = 'user';
            $action = 'index';
        }
        return parent::find_route($alias, $action, $http_method);
    }

    /**
     * Helper function to know if we need to generate / guess sexy URLs. This
     * method will query the application's config.
     *
     * @return
     *   TRUE if sexy URLs option is used, FALSE otherwise.
     */
    protected function use_sexy_urls()
    {
        return AppConfig::get('routing.rewrite', false);
    }

    /**
     * This method will parse the given $request_uri (usually
     * $_SERVER['REQUEST_URI']) and hack $_REQUEST. This is needed for the
     * handling of sexy URLs. If the sexy URLs option is off, then nothing is
     * done.
     *
     * The sexy URLs scheme is one of:
     * - /controller/action
     * - /controller/action/id
     * - /controller/action/arg0/value0/arg1/value1/...
     */
    public function decode_url($request_uri)
    {
        if ($this->use_sexy_urls()) {
            $base = dirname($_SERVER['SCRIPT_NAME']);
            // add a trailing slash `/' if needed.
            if ($base !== '/')
                $base .= '/';
            $url_data = parse_url($request_uri);
            $path     = $url_data['path'];
            $args     = explode('/', preg_replace('/^' . preg_quote($base, '/') . '/', '', $path));
            // if $args contains less than 2 element it is expected that
            // despite the configuration, the URL is not sexy formated.
            if (count($args) >= 2) {
                // hack $_REQUEST
                $_REQUEST['controller'] = array_shift($args);
                $_REQUEST['action']     = array_shift($args);
                if (count($args) === 1) {
                    $_REQUEST['id'] = array_shift($args);
                } else {
                    foreach(array_chunk($args, 2) as $values) {
                        if (!array_key_exists($values[0], $_REQUEST))
                            $_REQUEST[$values[0]] = $values[1];
                    }
                }
            }
        }
    }

    /**
     * URL generator.
     *
     * This method translate an array representing an application route into an
     * URL.
     *
     * <b>Example</b>
     * @code
     *   <a href="<?php print h($router->url_for(['controller' => 'file', 'action' => 'download'])); ?>">
     *      Download now!
     *   </a>
     * @endcode
     *
     * @param $parameters
     *   An array of URL parameters.
     *
     * @param $base_url
     *   The base url used (host and path). The default is to use the
     *   base_url() method. Setting this argument allow to generate routes for
     *   hosts / application different than this application.
     *
     * @return
     *  a full URL where the given action can be called.
     *
     * @note
     *   This method is very simple and won't check if the route is valid.
     */
    public function url_for($parameters, $base_url = null)
    {
        if (!is_null($base_url))
            $url = $base_url;
        else
            $url = $this->base_url();

        if ($this->use_sexy_urls()) {
            $url .=       rawurlencode($parameters['controller']);
            $url .= '/' . rawurlencode($parameters['action']);
            unset($parameters['controller']);
            unset($parameters['action']);

            if (count($parameters) === 1 && array_key_exists('id', $parameters))
                $url .= '/' . rawurlencode($parameters['id']);
            else {
                foreach ($parameters as $key => $value)
                    $url .= '/' . rawurlencode($key) . '/' . rawurlencode($value);
            }
        } else {
            $url .= 'index.php';
            if (!empty($parameters))
                $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }

    /**
     * Root (as homepage) URL.
     */
    public function root_url()
    {
        return $this->base_url();
    }

    public function login_url()
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'login'
        ]);
    }

    public function logout_url()
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'logout'
        ]);
    }

    public function new_user_url()
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'new',
        ]);
    }

    public function create_user_url()
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'create',
        ]);
    }

    public function users_url()
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'index',
        ]);
    }

    public function user_url($user)
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'show',
            'id'         => $user->id
        ]);
    }

    public function edit_user_url($user)
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'edit',
            'id'         => $user->id
        ]);
    }

    public function update_user_url($user)
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'update',
            'id'         => $user->id
        ]);
    }

    public function disable_user_url($user)
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'disable',
            'id'         => $user->id
        ]);
    }

    public function enable_user_url($user)
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'enable',
            'id'         => $user->id
        ]);
    }

    public function destroy_user_url($user)
    {
        return $this->url_for([
            'controller' => 'user',
            'action'     => 'destroy',
            'id'         => $user->id
        ]);
    }
}
