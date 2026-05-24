<?php

namespace App\Services;

use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

class TwoFactorAuthService
{
    /**
     * Generate a new secret key for TOTP
     */
    public function generateSecret(): string
    {
        return trim(Base32::encodeUpper(random_bytes(32)), '=');
    }

    /**
     * Generate QR code URL for authenticator apps
     */
    public function getQRCodeUrl(string $email, string $secret, string $issuer = 'Waka Line Logistics'): string
    {
        $totp = TOTP::create($secret);
        $totp->setLabel($email);
        $totp->setIssuer($issuer);
        
        return $totp->getQrCodeUri(
            'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($totp->getProvisioningUri()),
            urlencode($totp->getProvisioningUri())
        );
    }

    /**
     * Get the provisioning URI for manual entry
     */
    public function getProvisioningUri(string $email, string $secret, string $issuer = 'Waka Line Logistics'): string
    {
        $totp = TOTP::create($secret);
        $totp->setLabel($email);
        $totp->setIssuer($issuer);
        
        return $totp->getProvisioningUri();
    }

    /**
     * Verify a TOTP code
     */
    public function verifyCode(string $secret, string $code): bool
    {
        $totp = TOTP::create($secret);
        
        // Allow 1 period (30 seconds) of leeway in either direction
        return $totp->verify($code, null, 1);
    }

    /**
     * Generate recovery codes
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        }
        
        return $codes;
    }

    /**
     * Verify a recovery code
     */
    public function verifyRecoveryCode(array $recoveryCodes, string $code): bool
    {
        return in_array(strtoupper($code), array_map('strtoupper', $recoveryCodes));
    }

    /**
     * Remove a used recovery code
     */
    public function removeRecoveryCode(array $recoveryCodes, string $code): array
    {
        return array_values(array_filter($recoveryCodes, function($recoveryCode) use ($code) {
            return strtoupper($recoveryCode) !== strtoupper($code);
        }));
    }
}
