<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class MarathonPointsExport implements FromArray, WithHeadings, WithStyles
{
    protected $users;
    protected $groupName;
    protected $marathonTitle;

    public function __construct($users)
    {
        $this->users = $users;
        $this->groupName = $users[0]['group_name'];
        $this->marathonTitle = $users[0]['osboha_marathon']['title'];
    }

    public function array(): array
    {
        $rows = [];

        // Loop through each user and add their data to the rows
        foreach ($this->users as $user) {
            $rows[] = [
                $user['user_name'],
                // $user['group_name'],
                // $user['osboha_marathon']['title'],
                $user['total_points'],
                $user['basic_points']['point_week_1'] ?? 0,
                $user['basic_points']['point_week_2'] ?? 0,
                $user['basic_points']['point_week_3'] ?? 0,
                $user['basic_points']['point_week_4'] ?? 0,
                $user['bonus_points'],
                $user['week_violations']['week_violations_1'] ?? 0,
                $user['week_violations']['week_violations_2'] ?? 0,
                $user['week_violations']['week_violations_3'] ?? 0,
                $user['week_violations']['week_violations_4'] ?? 0,
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['اسم المجموعة: ' . $this->groupName],               // Group Name title
            [' الماراثون: ' . $this->marathonTitle],        // Marathon Title
            [
                'اسم المستخدم',          // User Name
                'إجمالي النقاط',         // Total Points
                'نقاط الأسبوع الأول',     // Basic Point Week 1
                'نقاط الأسبوع الثاني',    // Basic Point Week 2
                'نقاط الأسبوع الثالث',    // Basic Point Week 3
                'نقاط الأسبوع الرابع',    // Basic Point Week 4
                'النقاط الإضافية',        // Bonus Points
                'مخالفات الأسبوع الأول',  // Violation Week 1
                'مخالفات الأسبوع الثاني',  // Violation Week 2
                'مخالفات الأسبوع الثالث',  // Violation Week 3
                'مخالفات الأسبوع الرابع'   // Violation Week 4
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set the sheet direction to right-to-left
        $sheet->setRightToLeft(true);

        // Style the title rows for group name and marathon title
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');

        $sheet->getStyle('A1:A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => '208040']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],

        ]);

        // Style the header row
        $sheet->getStyle('A3:M3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => '208040']
            ]
        ]);

        $sheet->getStyle('A1:M' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return [];
    }
}
