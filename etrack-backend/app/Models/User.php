<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role_id',
        'status',
        'last_login',
        'last_activity',
        'failed_login_attempts',
        'locked_until',
        'mfa_enabled',
        'mfa_secret',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login' => 'datetime',
            'last_activity' => 'datetime',
            'locked_until' => 'datetime',
            'mfa_enabled' => 'boolean',
        ];
    }

    /**
     * Get the role that owns the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the student profile for the user.
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Get the employee profile for the user.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the documents for the user.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the audit logs for the user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the MFA OTPs for the user.
     */
    public function mfaOtps(): HasMany
    {
        return $this->hasMany(MfaOtp::class);
    }

    /**
     * Check if MFA is enabled for the user.
     */
    public function isMfaEnabled(): bool
    {
        return $this->mfa_enabled;
    }

    /**
     * Enable MFA for the user.
     */
    public function enableMfa(): void
    {
        $this->update(['mfa_enabled' => true]);
    }

    /**
     * Disable MFA for the user.
     */
    public function disableMfa(): void
    {
        $this->update(['mfa_enabled' => false, 'mfa_secret' => null]);
        // Delete all existing OTPs
        $this->mfaOtps()->delete();
    }
}
