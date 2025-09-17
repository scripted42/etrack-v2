<?php

namespace App\Services;

class PasswordPolicyService
{
    /**
     * Validate password strength according to ISO 27001 standards
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];
        
        // Minimum 8 characters
        if (strlen($password) < 8) {
            $errors[] = 'Password harus minimal 8 karakter';
        }
        
        // Must contain uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password harus mengandung huruf kapital';
        }
        
        // Must contain lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password harus mengandung huruf kecil';
        }
        
        // Must contain number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password harus mengandung angka';
        }
        
        // Must contain special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password harus mengandung karakter khusus (!@#$%^&*)';
        }
        
        // Check for common weak patterns
        $weakPatterns = [
            'password', '123456', 'qwerty', 'admin', 'user',
            '12345678', 'password123', 'admin123', 'user123'
        ];
        
        foreach ($weakPatterns as $pattern) {
            if (stripos($password, $pattern) !== false) {
                $errors[] = 'Password tidak boleh mengandung kata yang mudah ditebak';
                break;
            }
        }
        
        // Check for repeated characters (more than 3 consecutive)
        if (preg_match('/(.)\1{3,}/', $password)) {
            $errors[] = 'Password tidak boleh mengandung karakter yang berulang lebih dari 3 kali';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'strength' => self::calculateStrength($password)
        ];
    }
    
    /**
     * Calculate password strength score (0-100)
     */
    public static function calculateStrength(string $password): int
    {
        $score = 0;
        
        // Length bonus
        $length = strlen($password);
        if ($length >= 8) $score += 20;
        if ($length >= 12) $score += 10;
        if ($length >= 16) $score += 10;
        
        // Character variety bonus
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 10;
        
        // Pattern penalties
        if (preg_match('/(.)\1{2,}/', $password)) $score -= 10;
        if (preg_match('/123|abc|qwe/i', $password)) $score -= 15;
        
        return min(100, max(0, $score));
    }
    
    /**
     * Get password strength description
     */
    public static function getStrengthDescription(int $score): string
    {
        if ($score >= 80) return 'Sangat Kuat';
        if ($score >= 60) return 'Kuat';
        if ($score >= 40) return 'Sedang';
        if ($score >= 20) return 'Lemah';
        return 'Sangat Lemah';
    }
    
    /**
     * Generate secure password suggestion
     */
    public static function generateSuggestion(): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        $password = '';
        
        // Ensure at least one of each type
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $special[rand(0, strlen($special) - 1)];
        
        // Fill remaining length
        $all = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < 12; $i++) {
            $password .= $all[rand(0, strlen($all) - 1)];
        }
        
        return str_shuffle($password);
    }
}
