<?php

namespace Database\Seeders\Ramadan;

use App\Models\RamadanHadith;
use App\Models\RamadanQuestion;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class QuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::beginTransaction();
            $csv = fopen(base_path('database/data/ramadan_2024/ramadan_questions.csv'), 'r');
            $questions = [];

            while (($row = fgetcsv($csv)) !== false) {

                $questionsData = [
                    'time_to_publish' => $row[0],
                    'ramadan_day_id' => $row[1],
                    'title' => $row[2],
                    'link' => $row[3],
                    'question' => $row[4],
                    'category' => $row[5],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                // Validate input data
                $validator = Validator::make($questionsData, [
                    'time_to_publish' => 'required|String',
                    'ramadan_day_id' => 'required|int',
                    'title' => 'required|String',
                    'link' => 'required_if:category,التثقيف بالفيديو',
                    'question' => 'required|String',
                    'category' => 'required|String',
                ]);

                if ($validator->fails()) {
                    // Handle validation errors
                    continue;
                }

                $questions[] = $questionsData;
            }

            fclose($csv);

            RamadanQuestion::insert($questions);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
