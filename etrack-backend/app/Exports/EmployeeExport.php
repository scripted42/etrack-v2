<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithMapping
{
    protected $employees;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    public function collection()
    {
        return $this->employees;
    }

    public function headings(): array
    {
        return [
            'NIP',
            'Nama',
            'Jabatan',
            'Status',
            'Email',
            'Username',
            'No HP',
            'Alamat',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Agama',
            'QR Value',
            'Photo Path',
            'Tanggal Dibuat',
            'Tanggal Diupdate'
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->nip,
            $employee->nama,
            $employee->jabatan,
            $employee->status,
            $employee->user?->email ?? '',
            $employee->user?->username ?? '',
            $employee->contact?->no_hp ?? '',
            $employee->contact?->alamat ?? '',
            $employee->identity?->tanggal_lahir ? $employee->identity->tanggal_lahir->format('d/m/Y') : '',
            $employee->identity?->jenis_kelamin ?? '',
            $employee->identity?->agama ?? '',
            $employee->qr_value ?? '',
            $employee->photo_path ?? '',
            $employee->created_at?->format('d/m/Y H:i:s') ?? '',
            $employee->updated_at?->format('d/m/Y H:i:s') ?? ''
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
                    'startColor' => ['rgb' => '2196F3']
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // NIP
            'B' => 25, // Nama
            'C' => 20, // Jabatan
            'D' => 12, // Status
            'E' => 25, // Email
            'F' => 15, // Username
            'G' => 15, // No HP
            'H' => 30, // Alamat
            'I' => 15, // Tanggal Lahir
            'J' => 15, // Jenis Kelamin
            'K' => 12, // Agama
            'L' => 20, // QR Value
            'M' => 30, // Photo Path
            'N' => 20, // Tanggal Dibuat
            'O' => 20, // Tanggal Diupdate
        ];
    }
}
