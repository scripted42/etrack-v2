<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class QrCodeSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token',
        'qr_code_data',
        'generated_at',
        'expires_at',
        'is_active'
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Generate new QR code session
     */
    public static function generateNewSession(): self
    {
        $sessionToken = bin2hex(random_bytes(32));
        $qrCodeData = base64_encode(json_encode([
            'token' => $sessionToken,
            'timestamp' => now()->timestamp,
            'type' => 'attendance'
        ]));

        return self::create([
            'session_token' => $sessionToken,
            'qr_code_data' => $qrCodeData,
            'generated_at' => now(),
            'expires_at' => now()->addSeconds(10), // 10 seconds expiry
            'is_active' => true
        ]);
    }

    /**
     * Check if session is valid and not expired
     */
    public function isValid(): bool
    {
        return $this->is_active && $this->expires_at->isFuture();
    }

    /**
     * Mark session as used
     */
    public function markAsUsed(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Clean up expired sessions
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now())
                  ->orWhere('is_active', false)
                  ->delete();
    }

    /**
     * Get QR code data for display
     */
    public function getQrCodeDisplayData(): array
    {
        return [
            'token' => $this->session_token,
            'data' => $this->qr_code_data,
            'expires_in' => $this->expires_at->diffInSeconds(now()),
            'is_valid' => $this->isValid()
        ];
    }
}


