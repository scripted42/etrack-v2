<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditTrailExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithMapping
{
    protected $auditLogs;

    public function __construct($auditLogs)
    {
        $this->auditLogs = $auditLogs;
    }

    public function collection()
    {
        return $this->auditLogs;
    }

    public function headings(): array
    {
        return [
            'ID',
            'User',
            'Event Type',
            'Model',
            'Model ID',
            'Operation',
            'IP Address',
            'User Agent',
            'Details',
            'Timestamp'
        ];
    }

    public function map($auditLog): array
    {
        return [
            $auditLog->id,
            $auditLog->user?->name ?? 'System',
            $auditLog->event_type,
            $auditLog->model ?? '',
            $auditLog->model_id ?? '',
            $auditLog->operation ?? '',
            $auditLog->ip_address ?? '',
            $auditLog->user_agent ?? '',
            is_array($auditLog->details) ? json_encode($auditLog->details, JSON_PRETTY_PRINT) : $auditLog->details,
            $auditLog->created_at?->format('d/m/Y H:i:s') ?? ''
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
                    'startColor' => ['rgb' => 'FF9800']
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,  // ID
            'B' => 20, // User
            'C' => 20, // Event Type
            'D' => 15, // Model
            'E' => 10, // Model ID
            'F' => 15, // Operation
            'G' => 15, // IP Address
            'H' => 30, // User Agent
            'I' => 50, // Details
            'J' => 20, // Timestamp
        ];
    }
}



