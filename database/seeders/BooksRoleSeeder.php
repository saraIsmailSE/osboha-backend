<?php

namespace Database\Seeders;

use App\Models\BookLevel;
use App\Models\BookType;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class BooksRoleSeeder extends Seeder
{
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
