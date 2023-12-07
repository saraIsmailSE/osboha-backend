<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SupportLeaderRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $supportLeaderRole = Role::create(['name' => 'support_leader']);

        $supportLeaderRole->givePermissionTo('create post');
        $supportLeaderRole->givePermissionTo('delete post');
        $supportLeaderRole->givePermissionTo('edit post');
        $supportLeaderRole->givePermissionTo('pin post');
        $supportLeaderRole->givePermissionTo('create comment');
        $supportLeaderRole->givePermissionTo('delete comment');
        $supportLeaderRole->givePermissionTo('edit comment');
        $supportLeaderRole->givePermissionTo('create RequestAmbassador');
        $supportLeaderRole->givePermissionTo('edit RequestAmbassador');
        $supportLeaderRole->givePermissionTo('audit mark');
    }
}
