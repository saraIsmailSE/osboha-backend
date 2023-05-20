<?php

namespace Database\Seeders;

use App\Models\ModificationReason;
use Illuminate\Database\Seeder;

class ModificationReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $leader_reasons = [
            'عدد أحرف حقيقية أقل مما يجب',
            'الأطروحة تجميع اقتباسات وليس بها إضافة',
            'الأطروحة خالية من القيمة الحقيقية وبها حشو لزيادة الحروف',
            'صور الاقتباسات وهمية',
            'إدخال خاطىء آخر'
        ];

        foreach ($leader_reasons as $reason) {
            ModificationReason::create([
                'reason' => $reason,
                'level' => 'leader'
            ]);
        }

        // $advisor_reasons = [
        //     'Thesis is correct',
        //     'Thesis is complete',
        //     'Thesis is not complete',
        //     'Thesis is not correct',
        //     'Other'
        // ];

        // foreach ($advisor_reasons as $reason) {
        //     ModificationReason::create([
        //         'reason' => $reason,
        //         'level' => 'advisor'
        //     ]);
        // }
    }
}