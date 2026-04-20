<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * Tüm kullanıcıları listele (Admin/Koordinatör/Personel/Öğrenci)
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'participantProfile']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->role) {
            $query->role($request->role);
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * Kullanıcı rolünü güncelle
     */
    public function updateRole(Request $request, User $user)
    {
        // Güvenlik: Kendi rolünü değiştiremesin (Opsiyonel ama önerilir)
        if (auth()->id() === $user->id) {
             return response()->json(['message' => 'Kendi rolünüzü değiştiremezsiniz.'], 403);
        }

        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name'
        ]);

        $user->syncRoles($request->roles);

        return response()->json([
            'message' => "{$user->name} kullanıcısının rolleri güncellendi.",
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Kullanıcı sil (Yumuşak silme if using SoftDeletes)
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
             return response()->json(['message' => 'Kendinizi silemezsiniz.'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Kullanıcı silindi.']);
    }
}
