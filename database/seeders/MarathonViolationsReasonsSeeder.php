<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarathonViolationsReasonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('marathon_violations_reasons')->insert([
            [
                'reason' => 'احتساب الصفحات الفارغة والفهارس والمراجع وتأثيرها على عدد صفحات',
                'points' => 5
            ],
            [
                'reason' => 'اطروحة مختلفة عن موضوع الكتاب.',
                'points' => 5
            ],
            [
                'reason' => 'أخذ اقتباس حرفي من الكتاب ووضعه على شكل أطروحة.',
                'points' => 50
            ],
            [
                'reason' => 'أخذ اطروحة من موقع إلكتروني.',
                'points' => 50
            ]
        ]);
    }
}
