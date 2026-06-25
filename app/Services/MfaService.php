<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

class MfaService
{
    public function generateOtp(User $user): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'mfa_otp' => $otp,
            'mfa_otp_expires_at' => now()->addMinutes(config('inventory.mfa_otp_expiry_minutes')),
        ]);

        Mail::raw(
            "Your MFA verification code is: {$otp}. It expires in ".config('inventory.mfa_otp_expiry_minutes').' minutes.',
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('MFA Verification Code');
            }
        );

        return $otp;
    }

    public function verifyOtp(User $user, string $otp): bool
    {
        if (! $user->mfa_otp || ! $user->mfa_otp_expires_at) {
            return false;
        }

        if ($user->mfa_otp_expires_at->isPast()) {
            return false;
        }

        if (! hash_equals($user->mfa_otp, $otp)) {
            return false;
        }

        $user->update([
            'mfa_otp' => null,
            'mfa_otp_expires_at' => null,
        ]);

        return true;
    }
}
