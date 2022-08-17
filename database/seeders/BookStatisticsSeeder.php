<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BookStatistics;

class BookStatisticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        BookStatistics::create();
    }
}
