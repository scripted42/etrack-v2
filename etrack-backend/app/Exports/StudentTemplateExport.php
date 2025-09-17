<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                '91001',
                'Rizky',
                '7A',
                'aktif',
                '12345678',
                'Surabaya',
                '1/2/2012',
                'L',
                'Islam',
                '811111111',
                'Bapak Satu',
                'Ayah',
                '811111110'
            ],
            [
                '91002',
                'Donna',
                '7B',
                'aktif',
                '12345679',
                'Sidoarjo',
                '3/4/2012',
                'P',
                'Kristen',
                '822222222',
                'Ibu Dua',
                'Ibu',
                '822222220'
            ],
            [
                '91003',
                'Ahmad Fauzi',
                '8A',
                'aktif',
                '12345680',
                'Malang',
                '5/6/2011',
                'L',
                'Islam',
                '833333333',
                'Bapak Tiga',
                'Ayah',
                '833333330'
            ],
            [
                '91004',
                'Siti Aminah',
                '8B',
                'aktif',
                '12345681',
                'Jakarta',
                '7/8/2011',
                'P',
                'Islam',
                '844444444',
                'Ibu Empat',
                'Ibu',
                '844444440'
            ],
            [
                '91005',
                'Budi Santoso',
                '9A',
                'aktif',
                '12345682',
                'Bandung',
                '9/10/2010',
                'L',
                'Kristen',
                '855555555',
                'Bapak Lima',
                'Ayah',
                '855555550'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'nis',
            'nama',
            'kelas',
            'status',
            'nisn',
            'tempat_lahir',
            'tanggal_lahir',
            'jenis_kelamin',
            'agama',
            'no_hp',
            'wali_nama',
            'wali_hubungan',
            'wali_no_hp'
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
            'A' => 15, // nis
            'B' => 25, // nama
            'C' => 10, // kelas
            'D' => 15, // status
            'E' => 15, // nisn
            'F' => 20, // tempat_lahir
            'G' => 15, // tanggal_lahir
            'H' => 15, // jenis_kelamin
            'I' => 15, // agama
            'J' => 15, // no_hp
            'K' => 25, // wali_nama
            'L' => 15, // wali_hubungan
            'M' => 15, // wali_no_hp
        ];
    }
}
