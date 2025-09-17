<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DataQualityService
{
    /**
     * Calculate data completeness score
     */
    public static function calculateDataCompleteness(): array
    {
        // Student data completeness
        $totalStudents = Student::count();
        $studentsWithCompleteData = Student::whereNotNull('nama')
            ->whereNotNull('nis')
            ->whereNotNull('kelas')
            ->whereNotNull('status')
            ->count();
        
        // Employee data completeness
        $totalEmployees = Employee::count();
        $employeesWithCompleteData = Employee::whereNotNull('nama')
            ->whereNotNull('nip')
            ->whereNotNull('jabatan')
            ->whereNotNull('status')
            ->count();
        
        $totalRecords = $totalStudents + $totalEmployees;
        $completeRecords = $studentsWithCompleteData + $employeesWithCompleteData;
        $completenessPercentage = $totalRecords > 0 ? round(($completeRecords / $totalRecords) * 100, 1) : 0;
        
        return [
            'total_students' => $totalStudents,
            'complete_students' => $studentsWithCompleteData,
            'incomplete_students' => $totalStudents - $studentsWithCompleteData,
            'total_employees' => $totalEmployees,
            'complete_employees' => $employeesWithCompleteData,
            'incomplete_employees' => $totalEmployees - $employeesWithCompleteData,
            'total_records' => $totalRecords,
            'complete_records' => $completeRecords,
            'incomplete_records' => $totalRecords - $completeRecords,
            'completeness_percentage' => $completenessPercentage
        ];
    }
    
    /**
     * Get incomplete data details
     */
    public static function getIncompleteData(): array
    {
        $incompleteStudents = Student::where(function($query) {
            $query->whereNull('nama')
                  ->orWhereNull('nis')
                  ->orWhereNull('kelas')
                  ->orWhereNull('status');
        })->get(['id', 'nama', 'nis', 'kelas', 'status']);
        
        $incompleteEmployees = Employee::where(function($query) {
            $query->whereNull('nama')
                  ->orWhereNull('nip')
                  ->orWhereNull('jabatan')
                  ->orWhereNull('status');
        })->get(['id', 'nama', 'nip', 'jabatan', 'status']);
        
        return [
            'incomplete_students' => $incompleteStudents,
            'incomplete_employees' => $incompleteEmployees
        ];
    }
    
    /**
     * Validate and fix data quality issues
     */
    public static function validateAndFixData(): array
    {
        $issues = [];
        $fixes = [];
        
        // Check for missing student data
        $studentsWithMissingData = Student::where(function($query) {
            $query->whereNull('nama')
                  ->orWhereNull('nis')
                  ->orWhereNull('kelas')
                  ->orWhereNull('status');
        })->get();
        
        foreach ($studentsWithMissingData as $student) {
            $studentIssues = [];
            
            if (empty($student->nama)) {
                $studentIssues[] = 'Nama tidak lengkap';
            }
            if (empty($student->nis)) {
                $studentIssues[] = 'NIS tidak lengkap';
            }
            if (empty($student->kelas)) {
                $studentIssues[] = 'Kelas tidak lengkap';
            }
            if (empty($student->status)) {
                $studentIssues[] = 'Status tidak lengkap';
            }
            
            if (!empty($studentIssues)) {
                $issues[] = [
                    'type' => 'student',
                    'id' => $student->id,
                    'name' => $student->nama ?: 'N/A',
                    'issues' => $studentIssues
                ];
            }
        }
        
        // Check for missing employee data
        $employeesWithMissingData = Employee::where(function($query) {
            $query->whereNull('nama')
                  ->orWhereNull('nip')
                  ->orWhereNull('jabatan')
                  ->orWhereNull('status');
        })->get();
        
        foreach ($employeesWithMissingData as $employee) {
            $employeeIssues = [];
            
            if (empty($employee->nama)) {
                $employeeIssues[] = 'Nama tidak lengkap';
            }
            if (empty($employee->nip)) {
                $employeeIssues[] = 'NIP tidak lengkap';
            }
            if (empty($employee->jabatan)) {
                $employeeIssues[] = 'Jabatan tidak lengkap';
            }
            if (empty($employee->status)) {
                $employeeIssues[] = 'Status tidak lengkap';
            }
            
            if (!empty($employeeIssues)) {
                $issues[] = [
                    'type' => 'employee',
                    'id' => $employee->id,
                    'name' => $employee->nama ?: 'N/A',
                    'issues' => $employeeIssues
                ];
            }
        }
        
        return [
            'issues' => $issues,
            'total_issues' => count($issues),
            'student_issues' => count(array_filter($issues, fn($issue) => $issue['type'] === 'student')),
            'employee_issues' => count(array_filter($issues, fn($issue) => $issue['type'] === 'employee'))
        ];
    }
    
    /**
     * Auto-fix common data quality issues
     */
    public static function autoFixData(): array
    {
        $fixes = [];
        
        // Fix students with missing status
        $studentsWithoutStatus = Student::whereNull('status')->get();
        foreach ($studentsWithoutStatus as $student) {
            $student->update(['status' => 'aktif']);
            $fixes[] = "Student {$student->nama} status set to 'aktif'";
        }
        
        // Fix employees with missing status
        $employeesWithoutStatus = Employee::whereNull('status')->get();
        foreach ($employeesWithoutStatus as $employee) {
            $employee->update(['status' => 'aktif']);
            $fixes[] = "Employee {$employee->nama} status set to 'aktif'";
        }
        
        // Fix students with missing kelas
        $studentsWithoutKelas = Student::whereNull('kelas')->get();
        foreach ($studentsWithoutKelas as $student) {
            $student->update(['kelas' => 'X']); // Default kelas
            $fixes[] = "Student {$student->nama} kelas set to 'X'";
        }
        
        return [
            'fixes' => $fixes,
            'total_fixes' => count($fixes)
        ];
    }
    
    /**
     * Get data quality recommendations
     */
    public static function getRecommendations(): array
    {
        $completeness = self::calculateDataCompleteness();
        $issues = self::validateAndFixData();
        
        $recommendations = [];
        
        if ($completeness['completeness_percentage'] < 90) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Lengkapi Data yang Hilang',
                'description' => "Kelengkapan data saat ini {$completeness['completeness_percentage']}%. Target minimal 90%.",
                'action' => 'Lengkapi data siswa dan pegawai yang belum lengkap',
                'impact' => 'Meningkatkan kelengkapan data ke 90%+'
            ];
        }
        
        if ($issues['student_issues'] > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Perbaiki Data Siswa',
                'description' => "Ada {$issues['student_issues']} siswa dengan data tidak lengkap.",
                'action' => 'Periksa dan lengkapi data siswa yang tidak lengkap',
                'impact' => 'Meningkatkan kualitas data siswa'
            ];
        }
        
        if ($issues['employee_issues'] > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Perbaiki Data Pegawai',
                'description' => "Ada {$issues['employee_issues']} pegawai dengan data tidak lengkap.",
                'action' => 'Periksa dan lengkapi data pegawai yang tidak lengkap',
                'impact' => 'Meningkatkan kualitas data pegawai'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate data quality score
     */
    public static function calculateQualityScore(): int
    {
        $completeness = self::calculateDataCompleteness();
        $issues = self::validateAndFixData();
        
        $score = $completeness['completeness_percentage'];
        
        // Deduct points for issues
        $score -= min(20, $issues['total_issues'] * 2);
        
        return max(0, round($score));
    }
    
    /**
     * Get data quality statistics
     */
    public static function getQualityStats(): array
    {
        $completeness = self::calculateDataCompleteness();
        $issues = self::validateAndFixData();
        $recommendations = self::getRecommendations();
        
        return [
            'completeness_percentage' => $completeness['completeness_percentage'],
            'total_records' => $completeness['total_records'],
            'complete_records' => $completeness['complete_records'],
            'incomplete_records' => $completeness['incomplete_records'],
            'total_issues' => $issues['total_issues'],
            'student_issues' => $issues['student_issues'],
            'employee_issues' => $issues['employee_issues'],
            'quality_score' => self::calculateQualityScore(),
            'recommendations' => $recommendations,
            'status' => self::getQualityStatus($completeness['completeness_percentage'])
        ];
    }
    
    /**
     * Get quality status based on percentage
     */
    private static function getQualityStatus(float $percentage): string
    {
        if ($percentage >= 95) return 'Excellent';
        if ($percentage >= 90) return 'Good';
        if ($percentage >= 80) return 'Fair';
        if ($percentage >= 70) return 'Poor';
        return 'Critical';
    }
}

