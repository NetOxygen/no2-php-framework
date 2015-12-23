<?php
require_once TESTSDIR . '/no2.unit.tests/models/role.class.php';
require_once TESTSDIR . '/no2.unit.tests/models/user.class.php';
/**
 * Tests the No2_ManyToManyModel class
 *
 * @coversDefaultClass No2_ManyToManyModel
 */
class No2_ManyToManyModelTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers ::many_to_many_set
     */
    function test_many_to_many_set()
    {
        $admin = User::all()->root()->select();
        $admin->set_roles([Role::ADMIN_ID]);

        $user_roles = array_map(function ($r) {
                return $r['role_id'];
            },
            No2_SQLQuery::execute('SELECT role_id FROM {users_roles_table} WHERE user_id = :id', [
                '{users_roles_table}'  => 'users_roles',
                ':id'                  => $admin->id
            ], [
                'return_as_collection' => true
            ])
        );

        $this->assertCount(1, $user_roles, 'User should have only one role');
        $this->assertContains(Role::ADMIN_ID, $user_roles, 'User should have admin role');

        $admin->set_roles([]);
        $user_roles = No2_SQLQuery::execute('SELECT role_id FROM {users_roles_table} WHERE user_id = :id', [
            '{users_roles_table}'  => 'users_roles',
            ':id'                  => $admin->id
        ], [
            'return_as_collection' => true
        ]);

        $this->assertEmpty($user_roles, 'User shouldn\'t have any role anymore');
    }

    /**
     * @covers ::many_to_many_get
     */
    function test_many_to_many_get()
    {
        $admin = User::all()->root()->select();
        No2_SQLQuery::execute('INSERT INTO {users_roles_table}(user_id, role_id) VALUES (:id, :admin_role_id), (:id, :anonymous_role_id)', [
            '{users_roles_table}'  => 'users_roles',
            ':id'                  => $admin->id,
            ':admin_role_id'       => Role::ADMIN_ID,
            ':anonymous_role_id'   => Role::ANONYMOUS_ID
        ]);

        $user_roles = array_map(function ($r) {
            return $r->id;
        }, $admin->roles()->select());

        $this->assertCount(2, $user_roles, 'User should have two roles');
        $this->assertContains(Role::ADMIN_ID, $user_roles, 'User should have admin role');
        $this->assertContains(Role::ANONYMOUS_ID, $user_roles, 'User should have anonymous role');
    }

    /**
     * @expectedException        LogicException
     * @expectedExceptionMessage many_to_many_set called on a new record
     */
    function test_many_to_many_set_with_a_new_record()
    {
        $user = new User();
        $user->set_roles([Role::ADMIN_ID]);
    }

    function test_many_to_many_get_with_a_new_record()
    {
        $user = new User();
        $user_roles = $user->roles()->select();
        $this->assertCount(0, $user_roles, 'User should have zero roles');
    }

    function test_many_to_many_set_when_already_in_a_transaction()
    {
        $this->assertTrue(No2_SQLQuery::_beginTransaction());
        $admin = User::all()->root()->select();
        $admin->set_roles([Role::ADMIN_ID]);
        $roles = $admin->roles()->select();
        $this->assertTrue(No2_SQLQuery::_commitTransaction());

        $this->assertCount(1, $roles, 'root should have exactly one role');
        $this->assertEquals(Role::ADMIN_ID, $roles[0]->id, 'the only root role should be ADMIN');
    }
}
