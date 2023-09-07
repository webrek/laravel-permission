<?php

namespace Webrek\Permission\Tests;

use Webrek\Permission\Exceptions\PermissionDoesNotExist;
use Webrek\Permission\Exceptions\WildcardPermissionInvalidArgument;
use Webrek\Permission\Exceptions\WildcardPermissionNotProperlyFormatted;
use Webrek\Permission\Models\Permission;
use Webrek\Permission\Tests\TestModels\User;
use Webrek\Permission\Tests\TestModels\WildcardPermission;

class WildcardHasPermissionsTest extends TestCase
{
    /** @test */
    public function it_can_check_wildcard_permission()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user1 = User::create(['email' => 'user1@test.com']);

        $permission1 = Permission::create(['name' => 'articles.edit,view,create']);
        $permission2 = Permission::create(['name' => 'news.*']);
        $permission3 = Permission::create(['name' => 'posts.*']);

        $user1->givePermissionTo([$permission1, $permission2, $permission3]);

        $this->assertTrue($user1->hasPermissionTo('posts.create'));
        $this->assertTrue($user1->hasPermissionTo('posts.create.123'));
        $this->assertTrue($user1->hasPermissionTo('posts.*'));
        $this->assertTrue($user1->hasPermissionTo('articles.view'));
        $this->assertFalse($user1->hasPermissionTo('projects.view'));
    }

    /**
     * @test
     *
     * @requires PHP >= 8.1
     */
    public function it_can_assign_wildcard_permissions_using_enums()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user1 = User::create(['email' => 'user1@test.com']);

        $articlesCreator = TestModels\TestRolePermissionsEnum::WildcardArticlesCreator;
        $newsEverything = TestModels\TestRolePermissionsEnum::WildcardNewsEverything;
        $postsEverything = TestModels\TestRolePermissionsEnum::WildcardPostsEverything;
        $postsCreate = TestModels\TestRolePermissionsEnum::WildcardPostsCreate;

        $permission1 = app(Permission::class)->findOrCreate($articlesCreator->value, 'web');
        $permission2 = app(Permission::class)->findOrCreate($newsEverything->value, 'web');
        $permission3 = app(Permission::class)->findOrCreate($postsEverything->value, 'web');

        $user1->givePermissionTo([$permission1, $permission2, $permission3]);

        $this->assertTrue($user1->hasPermissionTo($postsCreate));
        $this->assertTrue($user1->hasPermissionTo($postsCreate->value.'.123'));
        $this->assertTrue($user1->hasPermissionTo($postsEverything));

        $this->assertTrue($user1->hasPermissionTo(TestModels\TestRolePermissionsEnum::WildcardArticlesView));
        $this->assertTrue($user1->hasAnyPermission(TestModels\TestRolePermissionsEnum::WildcardArticlesView));

        $this->assertFalse($user1->hasPermissionTo(TestModels\TestRolePermissionsEnum::WildcardProjectsView));

        $user1->revokePermissionTo([$permission1, $permission2, $permission3]);

        $this->assertFalse($user1->hasPermissionTo(TestModels\TestRolePermissionsEnum::WildcardPostsCreate));
        $this->assertFalse($user1->hasPermissionTo($postsCreate->value.'.123'));
        $this->assertFalse($user1->hasPermissionTo(TestModels\TestRolePermissionsEnum::WildcardPostsEverything));

        $this->assertFalse($user1->hasPermissionTo(TestModels\TestRolePermissionsEnum::WildcardArticlesView));
        $this->assertFalse($user1->hasAnyPermission(TestModels\TestRolePermissionsEnum::WildcardArticlesView));
    }

    /** @test */
    public function it_can_check_wildcard_permissions_via_roles()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user1 = User::create(['email' => 'user1@test.com']);

        $user1->assignRole('testRole');

        $permission1 = Permission::create(['name' => 'articles,projects.edit,view,create']);
        $permission2 = Permission::create(['name' => 'news.*.456']);
        $permission3 = Permission::create(['name' => 'posts']);

        $this->testUserRole->givePermissionTo([$permission1, $permission2, $permission3]);

        $this->assertTrue($user1->hasPermissionTo('posts.create'));
        $this->assertTrue($user1->hasPermissionTo('news.create.456'));
        $this->assertTrue($user1->hasPermissionTo('projects.create'));
        $this->assertTrue($user1->hasPermissionTo('articles.view'));
        $this->assertFalse($user1->hasPermissionTo('articles.list'));
        $this->assertFalse($user1->hasPermissionTo('projects.list'));
    }

    /** @test */
    public function it_can_check_custom_wildcard_permission()
    {
        app('config')->set('permission.enable_wildcard_permission', true);
        app('config')->set('permission.wildcard_permission', WildcardPermission::class);

        $user1 = User::create(['email' => 'user1@test.com']);

        $permission1 = Permission::create(['name' => 'articles:edit;view;create']);
        $permission2 = Permission::create(['name' => 'news:@']);
        $permission3 = Permission::create(['name' => 'posts:@']);

        $user1->givePermissionTo([$permission1, $permission2, $permission3]);

        $this->assertTrue($user1->hasPermissionTo('posts:create'));
        $this->assertTrue($user1->hasPermissionTo('posts:create:123'));
        $this->assertTrue($user1->hasPermissionTo('posts:@'));
        $this->assertTrue($user1->hasPermissionTo('articles:view'));
        $this->assertFalse($user1->hasPermissionTo('posts.*'));
        $this->assertFalse($user1->hasPermissionTo('articles.view'));
        $this->assertFalse($user1->hasPermissionTo('projects:view'));
    }

    /** @test */
    public function it_can_check_custom_wildcard_permissions_via_roles()
    {
        app('config')->set('permission.enable_wildcard_permission', true);
        app('config')->set('permission.wildcard_permission', WildcardPermission::class);

        $user1 = User::create(['email' => 'user1@test.com']);

        $user1->assignRole('testRole');

        $permission1 = Permission::create(['name' => 'articles;projects:edit;view;create']);
        $permission2 = Permission::create(['name' => 'news:@:456']);
        $permission3 = Permission::create(['name' => 'posts']);

        $this->testUserRole->givePermissionTo([$permission1, $permission2, $permission3]);

        $this->assertTrue($user1->hasPermissionTo('posts:create'));
        $this->assertTrue($user1->hasPermissionTo('news:create:456'));
        $this->assertTrue($user1->hasPermissionTo('projects:create'));
        $this->assertTrue($user1->hasPermissionTo('articles:view'));
        $this->assertFalse($user1->hasPermissionTo('news.create.456'));
        $this->assertFalse($user1->hasPermissionTo('projects.create'));
        $this->assertFalse($user1->hasPermissionTo('articles:list'));
        $this->assertFalse($user1->hasPermissionTo('projects:list'));
    }

    /** @test */
    public function it_can_check_non_wildcard_permissions()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user1 = User::create(['email' => 'user1@test.com']);

        $permission1 = Permission::create(['name' => 'edit articles']);
        $permission2 = Permission::create(['name' => 'create news']);
        $permission3 = Permission::create(['name' => 'update comments']);

        $user1->givePermissionTo([$permission1, $permission2, $permission3]);

        $this->assertTrue($user1->hasPermissionTo('edit articles'));
        $this->assertTrue($user1->hasPermissionTo('create news'));
        $this->assertTrue($user1->hasPermissionTo('update comments'));
    }

    /** @test */
    public function it_can_verify_complex_wildcard_permissions()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user1 = User::create(['email' => 'user1@test.com']);

        $permission1 = Permission::create(['name' => '*.create,update,delete.*.test,course,finance']);
        $permission2 = Permission::create(['name' => 'papers,posts,projects,orders.*.test,test1,test2.*']);
        $permission3 = Permission::create(['name' => 'User::class.create,edit,view']);

        $user1->givePermissionTo([$permission1, $permission2, $permission3]);

        $this->assertTrue($user1->hasPermissionTo('invoices.delete.367463.finance'));
        $this->assertTrue($user1->hasPermissionTo('projects.update.test2.test3'));
        $this->assertTrue($user1->hasPermissionTo('User::class.edit'));
        $this->assertFalse($user1->hasPermissionTo('User::class.delete'));
        $this->assertFalse($user1->hasPermissionTo('User::class.*'));
    }

    /** @test */
    public function it_throws_exception_when_wildcard_permission_is_not_properly_formatted()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user1 = User::create(['email' => 'user1@test.com']);

        $permission = Permission::create(['name' => '*..']);

        $user1->givePermissionTo([$permission]);

        $this->expectException(WildcardPermissionNotProperlyFormatted::class);

        $user1->hasPermissionTo('invoices.*');
    }

    /** @test */
    public function it_can_verify_permission_instances_not_assigned_to_user()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user = User::create(['email' => 'user@test.com']);

        $userPermission = Permission::create(['name' => 'posts.*']);
        $permissionToVerify = Permission::create(['name' => 'posts.create']);

        $user->givePermissionTo([$userPermission]);

        $this->assertTrue($user->hasPermissionTo('posts.create'));
        $this->assertTrue($user->hasPermissionTo('posts.create.123'));
        $this->assertTrue($user->hasPermissionTo($permissionToVerify->id));
        $this->assertTrue($user->hasPermissionTo($permissionToVerify));
    }

    /** @test */
    public function it_can_verify_permission_instances_assigned_to_user()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user = User::create(['email' => 'user@test.com']);

        $userPermission = Permission::create(['name' => 'posts.*']);
        $permissionToVerify = Permission::create(['name' => 'posts.create']);

        $user->givePermissionTo([$userPermission, $permissionToVerify]);

        $this->assertTrue($user->hasPermissionTo('posts.create'));
        $this->assertTrue($user->hasPermissionTo('posts.create.123'));
        $this->assertTrue($user->hasPermissionTo($permissionToVerify));
        $this->assertTrue($user->hasPermissionTo($userPermission));
    }

    /** @test */
    public function it_can_verify_integers_as_strings()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user = User::create(['email' => 'user@test.com']);

        $userPermission = Permission::create(['name' => '8']);

        $user->givePermissionTo([$userPermission]);

        $this->assertTrue($user->hasPermissionTo('8'));
    }

    /** @test */
    public function it_throws_exception_when_permission_has_invalid_arguments()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user = User::create(['email' => 'user@test.com']);

        $this->expectException(WildcardPermissionInvalidArgument::class);

        $user->hasPermissionTo(['posts.create']);
    }

    /** @test */
    public function it_throws_exception_when_permission_id_not_exists()
    {
        app('config')->set('permission.enable_wildcard_permission', true);

        $user = User::create(['email' => 'user@test.com']);

        $this->expectException(PermissionDoesNotExist::class);

        $user->hasPermissionTo(6);
    }
}
