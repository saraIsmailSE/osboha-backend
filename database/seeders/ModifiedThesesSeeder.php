<?php

namespace Database\Seeders;

use App\Models\ModifiedTheses;
use App\Models\User;
use App\Models\Week;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ModifiedThesesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users_count = User::count();
        $weeks_count = Week::count();
        $status_arr = ['not_audited', 'accepted', 'rejected'];
        for ($i = 0; $i < $weeks_count; $i++) {
            for ($j = 0; $j < 10; $j++) {
                $status = $status_arr[rand(0, 2)];
                ModifiedTheses::create([
                    'modifier_reason_id' => rand(1, 5),
                    'status' => $status,
                    'user_id' => rand(1, $users_count),
                    'thesis_id' => rand(1, $users_count),
                    'week_id' => $i + 1,
                    'modifier_id' => rand(1, $users_count),
                    'head_modifier_id' => $status !== 'not_audited' ? rand(1, $users_count) : null,
                    'head_modifier_reason_id' => $status !== 'not_audited' ? rand(6, 10) : null,
                ]);
            }
        }
    }
}