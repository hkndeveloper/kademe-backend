<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    /**
     * Enable 2FA for user (Section 11.12 - Admin 2FA requirement)
     */
    public function enable(Request $request)
    {
        $user = Auth::user();

        // Check if user is admin/coordinator
        if (!$user->hasRole(['super-admin', 'coordinator'])) {
            return response()->json(['message' => '2FA sadece admin ve koordinatörler için zorunludur.'], 403);
        }

        // Generate a random secret (in production, use a proper TOTP library)
        $secret = bin2hex(random_bytes(20));

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now()
        ]);

        return response()->json([
            'message' => '2FA başarıyla aktive edildi.',
            'secret' => $secret // In production, show QR code instead
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null
        ]);

        return response()->json(['message' => '2FA devre dışı bırakıldı.']);
    }

    /**
     * Verify 2FA code during login (Section 11.12)
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->two_factor_secret !== $request->code) {
            return response()->json(['message' => 'Geçersiz güvenlik kodu.'], 422);
        }

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_secret' => null // Tek kullanımlık
        ]);

        return response()->json(['message' => '2FA doğrulama başarılı.']);
    }

    /**
     * Check if user has 2FA enabled
     */
    public function status()
    {
        $user = Auth::user();

        return response()->json([
            'enabled' => $user->two_factor_enabled,
            'confirmed' => $user->two_factor_confirmed_at !== null
        ]);
    }
}
