<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'tc_no' => 'required|string|max:11|unique:participant_profiles,tc_no',
            'phone' => 'nullable|string',
            'university' => 'nullable|string',
            'department' => 'nullable|string',
            'class' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'motivation_letter' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Hash::make($validated['password']),
        ]);

        // Şartname 4.1: Yeni kayıt olanlar başlangıçta Ziyaretçi/Misafir (guest) statüsündedir.
        $user->assignRole('guest');

        // Profil oluştur
        \App\Models\ParticipantProfile::create([
            'user_id' => $user->id,
            'tc_no' => $validated['tc_no'],
            'phone' => $validated['phone'] ?? null,
            'university' => $validated['university'] ?? null,
            'department' => $validated['department'] ?? null,
            'class' => $validated['class'] ?? null,
            'credits' => 0, // Henüz aktif katılımcı değil
            'status' => 'passive',
        ]);

        // Başvuru oluştur (Madde 10.2)
        \App\Models\Application::create([
            'user_id' => $user->id,
            'project_id' => $validated['project_id'],
            'motivation_letter' => $validated['motivation_letter'] ?? null,
            'status' => 'pending',
        ]);

        // Hoş Geldiniz Maili Gönder (Şartname 11.4)
        $commService = app(\App\Services\CommunicationService::class);
        $commService->sendEmail(
            $user->id, 
            $user->email, 
            'KADEME Sistemine Hoş Geldiniz!', 
            "Merhaba {$user->name},\n\nKADEME yönetim sistemine kaydınız ve başvurunuz başarıyla alınmıştır. Başvuru sonucunuz netleştiğinde size tekrar bilgilendirme yapılacaktır.\n\nBaşarılar dileriz!"
        );

        $token = $user->createToken('kademe-token')->plainTextToken;

        return response()->json([
            'message' => 'Basvurunuz basariyla alindi. Admin onayi sonrasi katilimci profiliniz aktif edilecektir.',
            'user' => $user->load(['roles', 'participantProfile']),
            'token' => $token,
            'roles' => $user->getRoleNames(),
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            /** @var User $user */
            $token = $user->createToken('kademe-token')->plainTextToken;

            $roles = $user->getRoleNames();
            
            // 2FA Admin Kontrolü (Madde 11.12)
            $requires2FA = false;
            if ($roles->contains('super-admin') || $roles->contains('coordinator')) {
                $requires2FA = true;
                
                // 2FA Kodu Üret ve Mail Gönder
                $twoFactorCode = rand(100000, 999999);
                $user->update([
                    'two_factor_secret' => $twoFactorCode,
                    'two_factor_confirmed_at' => null // Henüz doğrulanmadı
                ]);

                $commService = app(\App\Services\CommunicationService::class);
                $commService->sendEmail(
                    $user->id,
                    $user->email,
                    "KADEME Giriş Güvenlik Kodu",
                    "Merhaba {$user->name},\n\nSisteme giriş yapabilmek için güvenlik kodunuz: {$twoFactorCode}\n\nEğer bu girişi siz yapmadıysanız lütfen şifrenizi değiştirin."
                );
            }

            return response()->json([
                'user' => $user->load('roles'),
                'token' => $token,
                'roles' => $roles,
                'requires_2fa' => $requires2FA,
            ]);
        }

        return response()->json([
            'message' => 'Geçersiz giriş bilgileri.',
        ], 401);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load(['roles', 'participantProfile', 'applications.project', 'attendances.activity'])->loadCount('applications'),
            'roles' => $request->user()->getRoleNames(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Çıkış yapıldı.']);
    }

    /**
     * KVKK Modülleri (Madde 11.11) - Unutulma Hakkı ve Veri Silme/Maskeleme
     */
    public function forgetMe(Request $request)
    {
        $user = $request->user();
        
        // İşlemleri Logla
        \Illuminate\Support\Facades\Log::info("KVKK Unutulma Hakkı Talebi: UserID {$user->id}");

        // Kullanıcının kişisel profili varsa maskele veya sil
        if ($user->participantProfile) {
            $user->participantProfile->update([
                'phone' => '***',
                'status' => 'anonymized'
            ]);
        }

        // Tokenlarını sil
        $user->tokens()->delete();

        // İsteğe bağlı olarak soft delete veya isminin anonimleştirilmesi
        $user->update([
            'name' => 'Anonim Kullanıcı',
            'email' => 'anonim_' . $user->id . '@kademe.org',
            'password' => bcrypt(\Illuminate\Support\Str::random(16))
        ]);

        return response()->json(['message' => 'Üyeliğiniz ve kişisel verileriniz KVKK kapsamında silinmiş/maskelenmiştir.']);
    }
}
