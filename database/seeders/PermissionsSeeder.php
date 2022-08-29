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

        ###### MARK ######
        Permission::create(['name' => 'edit mark']);
        Permission::create(['name' => 'delete mark']);
        Permission::create(['name' => 'create mark']);
        Permission::create(['name' => 'audit mark']);
        Permission::create(['name' => 'reject mark']);
        Permission::create(['name' => 'accept mark']);

        ###### THESIS ######
        // Permission::create(['name' => 'delete thesis']);
        // Permission::create(['name' => 'create thesis']);
        
        ###### ARTICLE ######
        Permission::create(['name' => 'edit article']);
        Permission::create(['name' => 'delete article']);
        Permission::create(['name' => 'create article']);

        ###### ACTIVITY ######
        Permission::create(['name' => 'edit activity']);
        Permission::create(['name' => 'delete activity']);
        Permission::create(['name' => 'create activity']);

        ###### INFOGRAPHIC ######
        Permission::create(['name' => 'edit infographic']);
        Permission::create(['name' => 'delete infographic']);
        Permission::create(['name' => 'create infographic']);

        ###### INFOGRAPHICSERIES  ######
        Permission::create(['name' => 'edit infographicSeries']);
        Permission::create(['name' => 'delete infographicSeries']);
        Permission::create(['name' => 'create infographicSeries']);

        ###### REACTION ######
        Permission::create(['name' => 'edit reaction']);
        Permission::create(['name' => 'delete reaction']);
        Permission::create(['name' => 'create reaction']);

        ###### REQUEST AMBASSADOR######
        Permission::create(['name' => 'edit RequestAmbassador']);
        Permission::create(['name' => 'create RequestAmbassador']);

         ###### HIGH PRIORITY REQUEST######
         Permission::create(['name' => 'create highPriorityRequestAmbassador']);

        ###### BOOK ######
        Permission::create(['name' => 'edit book']);
        Permission::create(['name' => 'delete book']);
        Permission::create(['name' => 'create book']);
        Permission::create(['name' => 'audit book']);
        Permission::create(['name' => 'accept book']);
        Permission::create(['name' => 'reject book']);

        ###### EXCEPTION ######
        Permission::create(['name' => 'list pending exception']);
        

        ###### STATISTICS ######
        Permission::create(['name' => 'list statistics']);

        ###### NOTIFICATION ######
        Permission::create(['name' => 'notify user']);

        ###### EDIT USER ######
        Permission::create(['name' => 'block user']);
        Permission::create(['name' => 'unblock user']);
        Permission::create(['name' => 'exclude user']);
        Permission::create(['name' => 'unexclude user']);
        Permission::create(['name' => 'list freeze users']);
        Permission::create(['name' => 'un freeze user']);
        Permission::create(['name' => 'freeze user']);

        ###### ROLE ######
        Permission::create(['name' => 'list role']);
        Permission::create(['name' => 'list transactions']);
        Permission::create(['name' => 'assign role']);
        Permission::create(['name' => 'update role']);
    
        ###### SystemIssue ######
        Permission::create(['name' => 'list systemIssue']);
        Permission::create(['name' => 'update systemIssue']);
    
        ###### TimeLine ######
        Permission::create(['name' => 'main timeline']);
        Permission::create(['name' => 'edit timeline']);
        Permission::create(['name' => 'delete timeline']);
        Permission::create(['name' => 'create timeline']);
        Permission::create(['name' => 'list timelines']);
        
        ###### GROUP ######
        Permission::create(['name' => 'edit group']);
        Permission::create(['name' => 'delete group']);
        Permission::create(['name' => 'create group']);
        Permission::create(['name' => 'list groups']);
        Permission::create(['name' => 'post in group']);
        Permission::create(['name' => 'add members to group']);
        Permission::create(['name' => 'delete members from group']);
        Permission::create(['name' => 'list group statistics']);

        ###### CHALLENGE ######
        Permission::create(['name' => 'edit challenge']);
        Permission::create(['name' => 'delete challenge']);
        Permission::create(['name' => 'create challenge']);

        ###### POST ######
        Permission::create(['name' => 'accept post']);
        Permission::create(['name' => 'decline post']);
        Permission::create(['name' => 'edit post']);
        Permission::create(['name' => 'delete post']);
        Permission::create(['name' => 'create post']);
        Permission::create(['name' => 'pin post']);

        ###### COMMENT ######
        Permission::create(['name' => 'edit comment']);
        Permission::create(['name' => 'delete comment']);
        Permission::create(['name' => 'create comment']);
        Permission::create(['name' => 'controll comments']);

        ###### ROOM ######
        Permission::create(['name' => 'create room']);
        Permission::create(['name' => 'room control']);

        ###### SECTION ######
        Permission::create(['name' => 'edit section']);
        Permission::create(['name' => 'delete section']);
        Permission::create(['name' => 'create section']);

        ###### Type ######
        Permission::create(['name' => 'edit type']);
        Permission::create(['name' => 'delete type']);
        Permission::create(['name' => 'create type']);

        // create roles and assign existing permissions

        $role1 = Role::create(['name' => 'admin']);
        $role2 = Role::create(['name' => 'advisor']);
        $role3 = Role::create(['name' => 'supervisor']);
        $role4 = Role::create(['name' => 'leader']);
        $role5 = Role::create(['name' => 'ambassador']);


        $role1->givePermissionTo(Permission::all());

        $role2->givePermissionTo('create group');
        $role2->givePermissionTo('create post');
        $role2->givePermissionTo('delete post');
        $role2->givePermissionTo('edit post');
        $role2->givePermissionTo('create comment');
        $role2->givePermissionTo('delete comment');
        $role2->givePermissionTo('edit comment');
        $role2->givePermissionTo('main timeline');

        $role3->givePermissionTo('create post');
        $role3->givePermissionTo('delete post');
        $role3->givePermissionTo('edit post');
        $role3->givePermissionTo('create comment');
        $role3->givePermissionTo('delete comment');
        $role3->givePermissionTo('edit comment');
        $role3->givePermissionTo('create RequestAmbassador');
        $role3->givePermissionTo('edit RequestAmbassador');
        $role3->givePermissionTo('create highPriorityRequestAmbassador');

        $role4->givePermissionTo('create post');
        $role4->givePermissionTo('delete post');
        $role4->givePermissionTo('edit post');
        $role4->givePermissionTo('create comment');
        $role4->givePermissionTo('delete comment');
        $role4->givePermissionTo('edit comment');
        $role4->givePermissionTo('create RequestAmbassador');
        $role4->givePermissionTo('edit RequestAmbassador');
 

        $role5->givePermissionTo('create post');
        $role5->givePermissionTo('delete post');
        $role5->givePermissionTo('edit post');
        $role5->givePermissionTo('create comment');
        $role5->givePermissionTo('delete comment');
        $role5->givePermissionTo('edit comment');


    }
}
