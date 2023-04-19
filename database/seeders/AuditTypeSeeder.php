<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $audit_type = ['full', 'variant', 'of_supervisor_audit','not_of_supervisor_audit'];
        //Audit TYPE
        $i = 0;
        while ($i <= 3) {
            DB::table('audit_types')->insert([

                'name' => $audit_type[$i],

            ]);
            $i++;
        }
    }
}