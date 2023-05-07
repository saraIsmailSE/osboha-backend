<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            ###### MARK ######
            'edit mark',
            'delete mark',
            'create mark',
            'audit mark',
            'reject thesis',
            'accept thesis',
            ###### ARTICLE ######
            'edit article',
            'delete article',
            'create article',
            ###### Activity ######
            'edit activity',
            'delete activity',
            'create activity',
            ###### INFOGRAPHIC ######
            'edit infographic',
            'delete infographic',
            'create infographic',
            ###### INFOGRAPHICSERIES  ######
            'edit infographicSeries',
            'delete infographicSeries',
            'create infographicSeries',
            ###### Reaction ######
            'edit reaction',
            'delete reaction',
            'create reaction',
            ###### RequestAmbassador ######
            'edit RequestAmbassador',
            'create RequestAmbassador',
            ###### HIGH PRIORITY REQUEST ######
            'create highPriorityRequestAmbassador',
            ###### Book ######
            'edit book',
            'delete book',
            'create book',
            'audit book',
            'accept book',
            'reject book',
            ###### EXCEPTION ######
            'list pending exception',
            ###### STATISTICS ######
            'list statistics',
            ###### NOTIFICATION ######
            'notify user',
            ###### EDIT USER ######
            'block user',
            'unblock user',
            'exclude user',
            'unexclude user',
            'list freeze users',
            'un freeze user',
            'freeze user',
            ###### ROLE ######
            'list role',
            'assign role',
            'update role',
            'list transactions',
            ###### SystemIssue ######
            'list systemIssue',
            'update systemIssue',
            ###### TimeLine ######
            'main timeline',
            'edit timeline',
            'delete timeline',
            'create timeline',
            'list timelines',
            ###### GROUP ######
            'edit group',
            'delete group',
            'create group',
            'list groups',
            'post in group',
            'add members to group',
            'delete members from group',
            'list group statistics',
            ###### CHALLENGE ######
            'edit challenge',
            'delete challenge',
            'create challenge',

            ###### POST ######
            'accept post',
            'decline post',
            'edit post',
            'delete post',
            'create post',
            'pin post',

            ###### ANNOUNCEMENT ######
            'edit announcement',
            'delete announcement',
            'create announcement',
            'pin announcement',

            ###### COMMENT ######
            'edit comment',
            'delete comment',
            'create comment',
            'control comments',

            ###### ROOM ######
            'create room',
            'room control',

            ###### SECTION ######
            'edit section',
            'delete section',
            'create section',

            ###### Type ######
            'edit type',
            'delete type',
            'create type',

            ###### Week ######
            'edit week',
        ];

        $permissionsToInsert = [];

        foreach ($permissions as $permission) {
            $permissionsToInsert[] = ['name' => $permission, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];
        }
        //insert permissions
        Permission::insert($permissionsToInsert);

        $role1 = Role::create(['name' => 'admin']);
        $role6 = Role::create(['name' => 'consultant']);
        $role2 = Role::create(['name' => 'advisor']);
        $role3 = Role::create(['name' => 'supervisor']);
        $role4 = Role::create(['name' => 'leader']);
        $role5 = Role::create(['name' => 'ambassador']);


        $role1->givePermissionTo(Permission::all());
        $role6->givePermissionTo(Permission::all());

        $role2->givePermissionTo('create group');
        $role2->givePermissionTo('create post');
        $role2->givePermissionTo('delete post');
        $role2->givePermissionTo('pin post');
        $role2->givePermissionTo('edit post');
        $role2->givePermissionTo('create comment');
        $role2->givePermissionTo('delete comment');
        $role2->givePermissionTo('edit comment');
        $role2->givePermissionTo('main timeline');
        $role2->givePermissionTo('edit announcement');
        $role2->givePermissionTo('delete announcement');
        $role2->givePermissionTo('create announcement');
        $role2->givePermissionTo('pin announcement');

        $role3->givePermissionTo('create post');
        $role3->givePermissionTo('delete post');
        $role3->givePermissionTo('edit post');
        $role3->givePermissionTo('pin post');
        $role3->givePermissionTo('create comment');
        $role3->givePermissionTo('delete comment');
        $role3->givePermissionTo('edit comment');
        $role3->givePermissionTo('create RequestAmbassador');
        $role3->givePermissionTo('edit RequestAmbassador');
        $role3->givePermissionTo('create highPriorityRequestAmbassador');

        $role4->givePermissionTo('create post');
        $role4->givePermissionTo('delete post');
        $role4->givePermissionTo('edit post');
        $role4->givePermissionTo('pin post');
        $role4->givePermissionTo('create comment');
        $role4->givePermissionTo('delete comment');
        $role4->givePermissionTo('edit comment');
        $role4->givePermissionTo('create RequestAmbassador');
        $role4->givePermissionTo('edit RequestAmbassador');


        $role5->givePermissionTo('create post');
        $role5->givePermissionTo('delete post');
        $role5->givePermissionTo('edit post');
        $role5->givePermissionTo('pin post');
        $role5->givePermissionTo('create comment');
        $role5->givePermissionTo('delete comment');
        $role5->givePermissionTo('edit comment');
    }
}