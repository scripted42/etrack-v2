<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DataQualityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DataQualityController extends Controller
{
    /**
     * Get data quality statistics
     */
    public function getQualityStats(): JsonResponse
    {
        try {
            $stats = DataQualityService::getQualityStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik kualitas data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get incomplete data
     */
    public function getIncompleteData(): JsonResponse
    {
        try {
            $incompleteData = DataQualityService::getIncompleteData();
            
            return response()->json([
                'success' => true,
                'data' => $incompleteData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data tidak lengkap',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate data quality
     */
    public function validateData(): JsonResponse
    {
        try {
            $validation = DataQualityService::validateAndFixData();
            
            return response()->json([
                'success' => true,
                'data' => $validation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memvalidasi data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-fix data quality issues
     */
    public function autoFixData(): JsonResponse
    {
        try {
            $fixes = DataQualityService::autoFixData();
            
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbaiki',
                'data' => $fixes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbaiki data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get data quality recommendations
     */
    public function getRecommendations(): JsonResponse
    {
        try {
            $recommendations = DataQualityService::getRecommendations();
            
            return response()->json([
                'success' => true,
                'data' => $recommendations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat rekomendasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate data completeness
     */
    public function calculateCompleteness(): JsonResponse
    {
        try {
            $completeness = DataQualityService::calculateDataCompleteness();
            
            return response()->json([
                'success' => true,
                'data' => $completeness
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung kelengkapan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
