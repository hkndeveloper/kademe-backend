<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParticipantProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $query = ParticipantProfile::with(['user' => function($q) {
            $q->withCount('badges'); // Optional, the append uses badges count
        }]);

        // Gelişmiş Filtreleme (Section 7 & 8)
        if ($request->filled('university')) {
            $query->where('university', 'like', '%' . $request->university . '%');
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('hometown')) {
            $query->where('hometown', 'like', '%' . $request->hometown . '%');
        }

        if ($request->filled('period')) {
            $query->where('period', $request->period);
        }

        if ($request->filled('min_age')) {
            $query->where('age', '>=', $request->min_age);
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->search, 'UTF-8');
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($u) use ($search) {
                    $u->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                })->orWhereRaw('LOWER(tc_no) LIKE ?', ["%{$search}%"]);
            });
        }

        $participants = $query->paginate(20);

        // KVKK Veri Maskeleme (Section 11.11)
        // Sadece yetkili adminler görmeli mantığı eklenebilir. Şimdilik temel transform:
        $participants->getCollection()->transform(function($p) {
            $p->tc_no_masked = $p->tc_no ? substr($p->tc_no, 0, 3) . '********' : null;
            $p->phone_masked = $p->phone ? substr($p->phone, 0, 4) . '***' . substr($p->phone, -2) : null;
            return $p;
        });

        return response()->json($participants);
    }

    public function store(Request $request)
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
            'hometown' => 'nullable|string',
            'period' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Hash::make($validated['password']),
        ]);

        $user->assignRole('student');

        $participant = ParticipantProfile::create([
            'user_id' => $user->id,
            'tc_no' => $validated['tc_no'],
            'phone' => $validated['phone'],
            'university' => $validated['university'],
            'department' => $validated['department'],
            'class' => $validated['class'],
            'hometown' => $validated['hometown'],
            'period' => $validated['period'],
            'credits' => 100,
            'status' => 'active',
        ]);

        return response()->json($participant->load('user'), 201);
    }

    public function show(ParticipantProfile $participant)
    {
        return response()->json($participant->load(['user', 'attendances.activity']));
    }

    public function update(Request $request, ParticipantProfile $participant)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $participant->user_id,
            'password' => 'nullable|string|min:8',
            'tc_no' => 'nullable|string|max:11|unique:participant_profiles,tc_no,' . $participant->id,
            'phone' => 'nullable|string',
            'university' => 'nullable|string',
            'department' => 'nullable|string',
            'class' => 'nullable|string',
            'hometown' => 'nullable|string',
            'period' => 'nullable|string',
            'credits' => 'nullable|integer',
            'status' => 'nullable|in:active,passive,alumni,blacklisted,failed',
            'digital_cv' => 'nullable|array',
        ]);

        // Katılımcı Profili Güncelleme
        $participant->update($validated);

        // Bağlı Kullanıcı Hesabını Güncelleme (Section 11.2)
        $userData = [];
        if ($request->filled('name')) $userData['name'] = $validated['name'];
        if ($request->filled('email')) $userData['email'] = $validated['email'];
        if ($request->filled('password')) $userData['password'] = \Hash::make($validated['password']);

        if (!empty($userData)) {
            $participant->user()->update($userData);
        }

        return response()->json($participant->load('user'));
    }
    
    /**
     * Katılımcı kredisi manuel güncelleme (Admin tarafından)
     */
    public function updateCredits(Request $request, ParticipantProfile $participant)
    {
        $validated = $request->validate([
            'credits' => 'required|integer',
            'reason' => 'nullable|string'
        ]);

        $oldCredits = $participant->credits;
        
        $participant->update([
            'credits' => $validated['credits']
        ]);
        
        // Audit Log Kaydı (Section 11.13)
        \App\Helpers\AuditHelper::log(
            'update_credits', 
            'ParticipantProfile', 
            $participant->id, 
            ['old_credits' => $oldCredits], 
            ['new_credits' => $participant->credits],
            $validated['reason'] ?? 'Manuel kredi güncelleme'
        );
        
        return response()->json([
            'message' => 'Kredi güncellendi.',
            'new_credits' => $participant->credits
        ]);
    }

    /**
     * Export participants to CSV
     * Section 8: Filtreleme sonuçları Excel, PDF, CSV formatlarında indirilebilir olmalıdır.
     */
    public function exportCsv(Request $request)
    {
        $query = $this->buildFilterQuery($request);
        $participants = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="katilimcilar_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($participants) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, ['ID', 'Ad Soyad', 'Universite', 'Bolum', 'Sinif', 'Telefon', 'E-posta', 'Kredi', 'Durum', 'Memleket']);
            
            // CSV Data
            foreach ($participants as $p) {
                fputcsv($file, [
                    $p->user_id,
                    $p->user->name ?? '',
                    $p->university ?? '',
                    $p->department ?? '',
                    $p->class ?? '',
                    $p->phone ?? '',
                    $p->user->email ?? '',
                    $p->credits,
                    $p->status,
                    $p->hometown ?? ''
                ]);
            }
            
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Export participants to Excel (using simple CSV format for now)
     * For proper Excel export, consider using Laravel Excel package
     */
    public function exportExcel(Request $request)
    {
        $query = $this->buildFilterQuery($request);
        $participants = $query->get();

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="katilimcilar_' . date('Y-m-d') . '.xls"',
        ];

        $html = '<table border="1">';
        $html .= '<tr><th>ID</th><th>Ad Soyad</th><th>Universite</th><th>Bolum</th><th>Sinif</th><th>Telefon</th><th>E-posta</th><th>Kredi</th><th>Durum</th></tr>';
        
        foreach ($participants as $p) {
            $html .= '<tr>';
            $html .= '<td>' . $p->user_id . '</td>';
            $html .= '<td>' . ($p->user->name ?? '') . '</td>';
            $html .= '<td>' . ($p->university ?? '') . '</td>';
            $html .= '<td>' . ($p->department ?? '') . '</td>';
            $html .= '<td>' . ($p->class ?? '') . '</td>';
            $html .= '<td>' . ($p->phone ?? '') . '</td>';
            $html .= '<td>' . ($p->user->email ?? '') . '</td>';
            $html .= '<td>' . $p->credits . '</td>';
            $html .= '<td>' . $p->status . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        return response($html)->withHeaders($headers);
    }

    public function exportPdf(Request $request)
    {
        $query = $this->buildFilterQuery($request);
        $participants = $query->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.participants_pdf', compact('participants'));

        return $pdf->download('kademe_katilimci_listesi.pdf');
    }

    /**
     * Build filtered query for exports
     */
    private function buildFilterQuery(Request $request)
    {
        $query = ParticipantProfile::with('user');

        if ($request->filled('university')) {
            $query->where('university', 'like', '%' . $request->university . '%');
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('hometown')) {
            $query->where('hometown', 'like', '%' . $request->hometown . '%');
        }

        if ($request->filled('period')) {
            $query->where('period', $request->period);
        }

        if ($request->filled('min_age')) {
            $query->where('age', '>=', $request->min_age);
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->search, 'UTF-8');
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($u) use ($search) {
                    $u->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                })->orWhereRaw('LOWER(tc_no) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query;
    }

    /**
     * Blacklist management (Section 14.1)
     * Otomatik Kara Liste Mekanizması
     */
    public function addToBlacklist(Request $request, ParticipantProfile $participant)
    {
        $participant->update([
            'status' => 'blacklisted',
            'blacklisted_at' => now(),
            'blacklist_reason' => $request->reason ?? 'Admin kararıyla'
        ]);

        // Send notification SMS
        $commService = app(\App\Services\CommunicationService::class);
        $message = "SİSTEMDEN ÇIKARILDINIZ: Hesabınız kara listeye alınmıştır. Detaylar için koordinatörle iletişime geçiniz.";
        $commService->sendSms($participant->user_id, $participant->phone, $message);

        // Send notification Email
        $user = $participant->user;
        if ($user) {
            $commService->sendEmail(
                $user->id,
                $user->email,
                'KADEME Sistem Bilgilendirmesi: Hesabınız Kısıtlandı',
                "Merhaba {$user->name},\n\nKADEME yönetim sistemi üzerindeki profiliniz, yönetici kararıyla 'Kara Liste' statüsüne alınmıştır. Bu durum, tüm aktif programlarınızın durdurulduğu ve yeni başvuruların engellendiği anlamına gelmektedir.\n\nİtiraz veya bilgi talebi için koordinatörlüğümüze başvurabilirsiniz."
            );
        }

        return response()->json(['message' => 'Katılımcı kara listeye eklendi ve bilgilendirildi']);
    }

    /**
     * Remove from blacklist
     */
    public function removeFromBlacklist(ParticipantProfile $participant)
    {
        $participant->update([
            'status' => 'active',
            'blacklisted_at' => null,
            'blacklist_reason' => null
        ]);

        return response()->json(['message' => 'Katılımcı kara listeden çıkarıldı']);
    }

    /**
     * Get blacklisted users
     */
    public function getBlacklisted()
    {
        $blacklisted = ParticipantProfile::where('status', 'blacklisted')
            ->with('user')
            ->get();

        return response()->json($blacklisted);
    }

    /**
     * Graduation status management (Section 15)
     * Dönem Sonu Durum İşaretleme
     */
    public function updateGraduationStatus(Request $request, ParticipantProfile $participant)
    {
        $request->validate([
            'graduation_status' => 'required|in:completed,graduated,failed',
            'project_id' => 'required|exists:projects,id',
            'reason' => 'nullable|string'
        ]);

        $participant->update([
            'graduation_status' => $request->graduation_status,
            'graduated_project_id' => $request->project_id,
            'graduation_reason' => $request->reason
        ]);

        // If graduated, automatically convert to alumni (Section 15.3)
        if ($request->graduation_status === 'graduated') {
            $this->convertToAlumni($participant->user_id);
        }

        return response()->json(['message' => 'Mezuniyet durumu güncellendi']);
    }

    /**
     * Convert user to alumni status (Section 15.3)
     */
    private function convertToAlumni($userId)
    {
        $user = User::findOrFail($userId);
        
        // Assign alumni role
        if (!$user->hasRole('alumni')) {
            $user->assignRole('alumni');
        }

        // Update participant profile status
        $profile = $participant = ParticipantProfile::where('user_id', $userId)->first();
        if ($profile) {
            $profile->update(['status' => 'alumni']);
        }

        // Log the transition
        \App\Helpers\AuditHelper::log(
            'convert_to_alumni',
            'User',
            $userId,
            ['old_status' => 'student'],
            ['new_status' => 'alumni'],
            'Otomatik mezuniyet dönüşümü'
        );
    }

    /**
     * Get alumni list (Section 15.3)
     */
    public function getAlumni()
    {
        $alumni = ParticipantProfile::where('status', 'alumni')
            ->with('user')
            ->get();

        return response()->json($alumni);
    }

    public function destroy(ParticipantProfile $participant)
    {
        $user = $participant->user;
        $participant->delete();
        if ($user) {
            $user->delete();
        }

        return response()->json(['message' => 'Katılımcı ve kullanıcı hesabı silindi.']);
    }
}
