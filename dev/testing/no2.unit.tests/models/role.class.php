<?php
/**
 * Test Role Model.
 *
 * @author
 *   Belkacem Alidra <belkacem.alidra@netoxygen.ch>
 */
require_once(PROJECTDIR . '/no2/many_to_many_model.trait.php');

class Role extends No2_AbstractModel
{
    use No2_ManyToManyModel;

    const ADMIN_ID     = 1;
    const ANONYMOUS_ID = 2;

    public static $table  = 'roles';

    public function db_infos() {
        return [
            'id'              => ['protected' => true],
            'name'           => []
        ];
    }
}
