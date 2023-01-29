<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $section= ['علمي', 'تاريخي', 'ديني', 'سياسي' , 'انجليزي' , 'ثقافي' ,'تربوي' ,'تنمية', 'سير الصحابة', 'اجتماعي'];
        for($i=0; $i<count($section); $i++){
            Section::create([
                'section' => $section[$i],
            ]);           
        }
    }
}
