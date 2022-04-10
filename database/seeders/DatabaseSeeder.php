<?php

namespace Database\Seeders;

use App\Models\RejectedMark;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            PermissionsSeeder::class,
            MarksSeeder::class,
            RejectedMarksSeeder::class,
            InfographicSeeder::class,
            InfographicSeriesSeeder::class,
            ArticleSeeder::class
        ]);
    }
}
