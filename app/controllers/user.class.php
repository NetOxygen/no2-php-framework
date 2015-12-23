<?php
/**
 * User Controller, manage user database entry and user related operations.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
require_once(APPDIR . '/controllers/base_controller.class.php');

class UserController extends BaseController {
    /**
     * the user ressource object (not the current user). If an id is given in
     * the request, the authorization mechanism will fetch the user from the db
     * and set this variable.
     *
     * In most cases this variable is forwarded to the view.
     */
    protected $user = NULL;

    /**
     * If an id is given in the request, we try to find the user row in the
     * database. If not found then the id is invalid and we return a 404 error.
     */
    protected function before_filter() {
        if (array_key_exists('id', $_REQUEST)) {
            $this->user = User::find($_REQUEST['id']);
            if (is_null($this->user))
                return No2_HTTP::NOT_FOUND;
        }
        // call authorization stuff.
        return parent::before_filter();
    }

    /**
     * authorization check. For each available action of this controller a
     * check is needed. We rely on the authorization framework in User and
     * Ability classes.
     */
    protected function authorize($user, $action) {
        switch ($action) {
        case 'login':  /* FALLTHROUGH */
        case 'logout':
            return $user->can($action);
        case 'new': /* FALLTHROUGH */
        case 'create':
            return $user->can('create', 'User');
        case 'index':
            return $user->can('read', 'User');
        case 'show':
            return $user->can('read', $this->user);
        case 'edit': /* FALLTHROUGH */
        case 'update':
            return $user->can('update', $this->user);
        case 'enable': /* FALLTHROUGH */
        case 'disable':
            return $user->can($action, $this->user);
        case 'destroy':
            return $user->can('destroy', $this->user);
        }
        return parent::authorize($user, $action);
    }

    /**
     * display the login form.
     */
    protected function GET_login() {
        // do nada.
    }

    /**
     * user authentication.
     */
    protected function POST_login() {
        $user = User::authenticate($_REQUEST['email'], $_REQUEST['cleartext']);
        if (is_null($user)) {
            $this->flash['error'] = t("Bad email or password.");
        } else {
            User::current_is($user);
            $this->flash['success'] = sprintf(t('Welcome back %s!'), h($user->fullname));

            global $router;
            $this->view->redirect_to($router->root_url());
        }
    }

    /**
     * user logout.
     *
     * FIXME:
     *   by pure lazyness, logout is handled by a GET method. it should be
     *   POST (and in a perfect REST world, maybe event a DELETE on a session
     *   resource).
     */
    protected function GET_logout() {
        User::current_is(NULL);
        $this->flash['info'] = t("You're logged out.");

        global $router;
        $this->view->redirect_to($router->root_url());
    }

    /**
     * create user form.
     */
    protected function GET_new() {
        $this->view->user = new User();
    }

    /**
     * create user action.
     */
    protected function POST_create() {
        $this->user = new User();
        $this->POST_update();
    }

    /**
     * show user action.
     */
    protected function GET_show() {
        $this->view->user = $this->user;
    }

    /**
     * list all the users.
     */
    protected function GET_index() {
        $this->view->users = User::all()->order_by('fullname')->select();
    }

    /**
     * update user form.
     */
    protected function GET_edit() {
        $this->view->user = $this->user;
    }

    /**
     * update user action.
     */
    protected function POST_update() {
        // password fields are handled in a special way.
        if (!empty($_REQUEST['new-password']) && $_REQUEST['new-password'] == $_REQUEST['new-password-confirmation'])
            $this->user->update_password($_REQUEST['new-password']);

        if (array_key_exists('role', $_REQUEST['user'])) {
            if (current_user()->can('pick-role', $this->user))
                $this->user->role = $_REQUEST['user']['role'];
            unset($_REQUEST['user']['role']);
        }

        $this->user->update_properties($_REQUEST['user']);

        if ($this->user->save()) {
            $this->flash['success'] = sprintf(t("The user %s (%s) has been saved."),
                h($this->user->fullname), h($this->user->email));
            global $router;
            $this->view->redirect_to($router->user_url($this->user));
        } else {
            $this->flash['error'] = sprintf(t("An error occured while trying to save the user %s (%s)"),
                h($this->user->fullname), h($this->user->email));
        }
        $this->view->user = $this->user;
    }

    /**
     * disable user action.
     */
    protected function POST_disable() {
        $this->user->is_active = false;

        if ($this->user->save()) {
            $this->flash['success'] = sprintf(t("The user %s (%s) has been successfully disabled."),
                h($this->user->fullname), h($this->user->email));
        } else {
            $this->flash['error'] = sprintf(t("An error occured while trying to disable the user %s (%s)"),
                h($this->user->fullname), h($this->user->email));
        }

        global $router;
        $this->view->redirect_to($router->user_url($this->user));
    }

    /**
     * enable user action.
     */
    protected function POST_enable() {
        $this->user->is_active = true;

        if ($this->user->save()) {
            $this->flash['success'] = sprintf(t("The user %s (%s) has been successfully enabled."),
                h($this->user->fullname), h($this->user->email));
        } else {
            $this->flash['error'] = sprintf(t("An error occured while trying to enable the user %s (%s)"),
                h($this->user->fullname), h($this->user->email));
        }

        global $router;
        $this->view->redirect_to($router->user_url($this->user));
    }

    /**
     * destroy user form.
     */
    protected function GET_destroy() {
        $this->view->user = $this->user;
    }

    /**
     * destroy user action.
     */
    protected function POST_destroy() {
        global $router;

        $this->user->destroy();
        $this->flash['success'] = sprintf(t("The user %s (%s) has been successfully destroyed."),
            h($this->user->fullname), h($this->user->email));
        $this->view->redirect_to($router->users_url());
    }
}
