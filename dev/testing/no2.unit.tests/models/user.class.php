<?php
/**
 * Test User Model.
 * This is based on the model used by the app.
 * The main difference is that the roles are now a many
 * to many relationship.
 *
 * @author
 *   Belkacem Alidra <belkacem.alidra@netoxygen.ch>
 */
require_once(PROJECTDIR . '/no2/many_to_many_model.trait.php');

class User extends No2_AbstractModel
{
    use No2_ManyToManyModel;

    const ROOT_ID =  1;

    public static $table  = 'users';

    public function db_infos()
    {
        return [
            'id'              => ['protected' => true],
            'fullname'        => []
        ];
    }

    /* test helper function to get the root user */
    public static function scope_root($q)
    {
        return $q->first()->id(static::ROOT_ID);
    }

    /* helper function to provide a many_to_many_set() interface. */
    public function set_roles($roles)
    {
        return $this->many_to_many_set(['user_id', 'users_roles', 'role_id'], $roles);
    }

    /* helper function to provide a many_to_many_get() interface. */
    public function roles()
    {
        return $this->many_to_many_get(['user_id', 'users_roles', 'role_id'], Role::all());
    }
}
