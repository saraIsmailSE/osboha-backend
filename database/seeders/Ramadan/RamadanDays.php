<?php

namespace Database\Seeders\Ramadan;

use App\Models\RamadanDay;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RamadanDays extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ramadanDays = [];
        $daysCount = 30;

        //start from 10th of March at 6:00 AM
        $startDate = Carbon::create(2024, 3, 10, 6, 0, 0);

        for ($i = 1; $i <= $daysCount; $i++) {
            $ramadanDays[] = [
                'day' => $i, 'is_active' => $i == 1 ? 1 : 0,
                'created_at' => $startDate->addDay()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ];
        }

        DB::beginTransaction();

        try {
            RamadanDay::insert($ramadanDays);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}
