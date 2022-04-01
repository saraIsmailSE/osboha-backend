<?php

namespace Database\Seeders;

use App\Models\RejectedMark;
use Illuminate\Database\Seeder;

class RejectedMarksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RejectedMark::create([
            'rejecter_note' => 'mark is wrong', 
            'is_acceptable' => null,
            'user_id' => '1',
            'thesis_id' => '1', 
            'week_id' => '1', 
            'rejecter_id' => '2'
        ]);

        RejectedMark::create([
            'rejecter_note' => 'check number of thesis again', 
            'is_acceptable' => null,
            'user_id' => '3',
            'thesis_id' => '5', 
            'week_id' => '3', 
            'rejecter_id' => '1'
        ]);

        RejectedMark::create([
            'rejecter_note' => 'number of pages is unrealistic', 
            'is_acceptable' => null,
            'user_id' => '2',
            'thesis_id' => '4', 
            'week_id' => '2', 
            'rejecter_id' => '3'
        ]);
    }
}
