<?php
/**
 * This class is used to concentrate the authorization code and logic. It
 * provide a very solid authorization framework based on user properties like
 * role.
 *
 * NOTE:
 *   While this file is stored in the models/ directory, it is not a model
 *   (there is no database storage), although it could become one.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class Ability extends No2_AbstractAbility
{
    /**
     * @override to implement authorization policies.
     */
    protected function initialize()
    {
        $user      = $this->user;
        $logged_in = (!$user->is_anonymous());

        parent::initialize();

        // Without resource
        $this->allow('login');
        $this->allow('logout', $logged_in);
        $this->allow('see_page_header', $logged_in);

        // User
        $this->allow('create', 'User', $user->is_admin());
        $this->allow('read',   'User', $logged_in);
        if ($user->is_admin()) {
            $this->allow('update', 'User');
        } else {
            $this->allow('update', 'User', ['id' => $user->id]);
        }
        $this->allow('destroy', 'User', $user->is_admin(), function ($target) {
            return (!$target->is_new_record() && !$target->is_special());
        });
        $this->allow(['pick-role', 'enable', 'disable'], 'User', $user->is_admin(), function ($target) {
            return !($target->is_admin() || $target->is_special());
        });
        $this->allow('read-abilities', 'User', $user->is_root());
    }

    /**
     * helper dumping an description of the abilities.
     */
    public function desc()
    {
        $desc = [];
        foreach ($this->rules as $action => $_) {
            foreach ($this->rules[$action] as $resource => $conditions) {
                if (!array_key_exists($resource, $desc))
                    $desc[$resource] = [];
                $desc[$resource][$action] = (
                    is_bool($conditions) && $conditions ? '' : 'only some'
                );
            }
        }
        return $desc;
    }
}
