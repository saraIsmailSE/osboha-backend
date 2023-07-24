<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class BooksRoleSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     *
     * @return void
     */
    public function run()
    {
        try {
            DB::beginTransaction();

            $bookRole = Role::create(['name' => 'book_quality_team']);

            $bookRole->givePermissionTo([
                'edit book',
                'delete book',
                'create book',
                'audit book',
                'accept book',
                'reject book'
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
