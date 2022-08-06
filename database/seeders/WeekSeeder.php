<?php

namespace Database\Seeders;

use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WeekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now()->startOfMonth()->subMonth(2)->startOfWeek(Carbon::SUNDAY);
        $titles = ['الأول من يونيو', 'الثاني من يونيو', 'الثالث من يونيو', 'الرابع من يونيو', 'الأول من يوليو', 'الثاني من يوليو', 'الثالث من يوليو', 'الرابع من يوليو', 'الأول من أغسطس'];
        for ($i = 0; $i < 8; $i++) {
            Week::create([
                'title' => $titles[$i],
                'date' => $now->addWeeks()->format('Y-m-d'),
            ]);
        }
    }
}