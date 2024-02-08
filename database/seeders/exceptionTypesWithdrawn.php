<?php

namespace Database\Seeders;

use App\Models\ExceptionType;
use Illuminate\Database\Seeder;

class exceptionTypesWithdrawn extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $exception_type = [
            ['type' => 'انسحاب مؤقت', 'created_at' => now(), 'updated_at' => now()],
        ];
        ExceptionType::insert($exception_type);
    }
}
