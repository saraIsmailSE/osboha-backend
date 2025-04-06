<?php

namespace App\Exports;

use Illuminate\Support\Facades\Log;
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

        foreach ($this->users as $user) {
            $rows[] = [
                $user['user_name'],
                $user['total_points'],
                $user['basic_points']['point_week_1'] ?? 0,
                $user['week_bonuses']['week_bonuses_1'] ?? 0,
                $user['week_violations']['week_violations_1'] ?? 0,
                $user['basic_points']['point_week_2'] ?? 0,
                $user['week_bonuses']['week_bonuses_2'] ?? 0,
                $user['week_violations']['week_violations_2'] ?? 0,
                $user['basic_points']['point_week_3'] ?? 0,
                $user['week_bonuses']['week_bonuses_3'] ?? 0,
                $user['week_violations']['week_violations_3'] ?? 0,
                $user['basic_points']['point_week_4'] ?? 0,
                $user['week_bonuses']['week_bonuses_4'] ?? 0,
                $user['week_violations']['week_violations_4'] ?? 0,
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['اسم المجموعة: ' . $this->groupName],
            ['الماراثون: ' . $this->marathonTitle],
            [
                'اسم المستخدم',
                'إجمالي النقاط',
                'الأسبوع الأول',
                '',
                '',
                'الأسبوع الثاني',
                '',
                '',
                'الأسبوع الثالث',
                '',
                '',
                'الأسبوع الرابع',
                '',
                '',
            ],
            [
                '',
                '',
                'النقاط الأساسية',
                'النقاط الإضافية',
                'نقاط المخالفات',
                'النقاط الأساسية',
                'النقاط الإضافية',
                'نقاط المخالفات',
                'النقاط الأساسية',
                'النقاط الإضافية',
                'نقاط المخالفات',
                'النقاط الأساسية',
                'النقاط الإضافية',
                'نقاط المخالفات',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set the sheet direction to right-to-left
        $sheet->setRightToLeft(true);

        // Merge cells for week headings
        $sheet->mergeCells('C3:E3'); // Week 1
        $sheet->mergeCells('F3:H3'); // Week 2
        $sheet->mergeCells('I3:K3'); // Week 3
        $sheet->mergeCells('L3:N3'); // Week 4

        // Style the title rows
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');

        $sheet->getStyle('A1:A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => '208040'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:N3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => '208040'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A4:N4')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Add borders to the entire table
        $sheet->getStyle('A1:N' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return [];
    }
}
