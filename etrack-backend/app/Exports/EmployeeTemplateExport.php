<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                '196501011990031001',
                'Dr. Suryadi, M.Pd',
                'Kepala Sekolah',
                'aktif',
                'suryadi@sekolah.sch.id'
            ],
            [
                '196502021990031002',
                'Dra. Siti Aminah, M.Pd',
                'Wakil Kepala Sekolah',
                'aktif',
                'siti.aminah@sekolah.sch.id'
            ],
            [
                '196503031990031003',
                'Ahmad Fauzi, S.Pd',
                'Guru Matematika',
                'aktif',
                'ahmad.fauzi@sekolah.sch.id'
            ],
            [
                '196504041990031004',
                'Sari Dewi, S.Pd',
                'Guru Bahasa Indonesia',
                'aktif',
                'sari.dewi@sekolah.sch.id'
            ],
            [
                '196505051990031005',
                'Budi Hartono, S.Pd',
                'Guru IPA',
                'aktif',
                'budi.hartono@sekolah.sch.id'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'NIP',
            'Nama',
            'Jabatan',
            'Status',
            'Email'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // NIP
            'B' => 30, // Nama
            'C' => 25, // Jabatan
            'D' => 15, // Status
            'E' => 30, // Email
        ];
    }
}



