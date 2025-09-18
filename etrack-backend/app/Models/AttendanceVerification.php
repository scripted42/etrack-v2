<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'gps_location',
        'selfie_photo_path',
        'qr_code_scanned',
        'device_info',
        'ip_address',
        'verified_at'
    ];

    protected $casts = [
        'gps_location' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the attendance that owns the verification
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Check if GPS location is within school radius
     */
    public function isLocationValid(float $schoolLat, float $schoolLng, float $radiusKm = 0.5): bool
    {
        $distance = $this->calculateDistance(
            $this->gps_location['lat'],
            $this->gps_location['lng'],
            $schoolLat,
            $schoolLng
        );

        return $distance <= $radiusKm;
    }

    /**
     * Calculate distance between two GPS coordinates
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * Get verification status
     */
    public function getVerificationStatusAttribute(): array
    {
        return [
            'gps_verified' => !empty($this->gps_location),
            'selfie_verified' => !empty($this->selfie_photo_path),
            'qr_verified' => !empty($this->qr_code_scanned),
            'all_verified' => !empty($this->gps_location) && 
                            !empty($this->selfie_photo_path) && 
                            !empty($this->qr_code_scanned)
        ];
    }
}


