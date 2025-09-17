<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithMapping
{
    protected $students;

    public function __construct($students)
    {
        $this->students = $students;
    }

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        return [
            'NIS',
            'Nama',
            'Kelas',
            'Status',
            'NISN',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Agama',
            'No HP',
            'Email',
            'Username',
            'Wali Nama',
            'Wali Hubungan',
            'Wali No HP',
            'QR Value',
            'Photo Path',
            'Tanggal Dibuat',
            'Tanggal Diupdate'
        ];
    }

    public function map($student): array
    {
        // Get first guardian if exists
        $guardian = $student->guardians->first();
        
        return [
            $student->nis,
            $student->nama,
            $student->kelas,
            $student->status,
            $student->identity?->nisn ?? '',
            $student->identity?->tempat_lahir ?? '',
            $student->identity?->tanggal_lahir ? (is_string($student->identity->tanggal_lahir) ? $student->identity->tanggal_lahir : $student->identity->tanggal_lahir->format('d/m/Y')) : '',
            $student->identity?->jenis_kelamin ?? '',
            $student->identity?->agama ?? '',
            $student->contact?->no_hp ?? '',
            $student->user?->email ?? '',
            $student->user?->username ?? '',
            $guardian?->nama ?? '',
            $guardian?->hubungan ?? '',
            $guardian?->no_hp ?? '',
            $student->qr_value ?? '',
            $student->photo_path ?? '',
            $student->created_at ? (is_string($student->created_at) ? $student->created_at : $student->created_at->format('d/m/Y H:i:s')) : '',
            $student->updated_at ? (is_string($student->updated_at) ? $student->updated_at : $student->updated_at->format('d/m/Y H:i:s')) : ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4CAF50']
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // NIS
            'B' => 25, // Nama
            'C' => 10, // Kelas
            'D' => 12, // Status
            'E' => 15, // NISN
            'F' => 20, // Tempat Lahir
            'G' => 15, // Tanggal Lahir
            'H' => 15, // Jenis Kelamin
            'I' => 12, // Agama
            'J' => 15, // No HP
            'K' => 25, // Email
            'L' => 15, // Username
            'M' => 20, // Wali Nama
            'N' => 15, // Wali Hubungan
            'O' => 15, // Wali No HP
            'P' => 20, // QR Value
            'Q' => 30, // Photo Path
            'R' => 20, // Tanggal Dibuat
            'S' => 20, // Tanggal Diupdate
        ];
    }
}
