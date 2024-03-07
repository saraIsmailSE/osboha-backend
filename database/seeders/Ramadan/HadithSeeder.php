<?php

namespace Database\Seeders\Ramadan;

use App\Models\RamadanHadith;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HadithSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        try {
            DB::beginTransaction();
            $csv = fopen(base_path('database/data/ramadan_2024/ramadan_hadiths.csv'), 'r');
            $hadiths = [];

            while (($row = fgetcsv($csv)) !== false) {

                $hadithData = [
                    'ramadan_day_id' => $row[0],
                    'hadith_title' => $row[1],
                    'hadith' => $row[2],
                    'image' => $row[3],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),

                ];

                // Validate input data
                $validator = Validator::make($hadithData, [
                    'ramadan_day_id' => 'required|int',
                    'hadith_title' => 'required|String',
                    'hadith' => 'required|String',
                    'image' => 'required|String',
                ]);

                if ($validator->fails()) {
                    // Handle validation errors
                    continue;
                }

                $hadiths[] = $hadithData;
            }

            fclose($csv);

            RamadanHadith::insert($hadiths);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
