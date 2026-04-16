<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CoordinatorController extends Controller
{
    public function index()
    {
        // Sadece koordinatör rolüne sahip kullanıcıları getir
        $coordinators = User::role('coordinator')->get();
        return response()->json($coordinators);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('coordinator');

        return response()->json(['message' => 'Koordinatör başarıyla oluşturuldu.', 'user' => $user], 201);
    }

    public function update(Request $request, User $coordinator)
    {
        // Gelen kullanıcının koordinatör olduğundan emin olalım
        if (!$coordinator->hasRole('coordinator')) {
            return response()->json(['message' => 'Bu kullanıcı bir koordinatör değil.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $coordinator->id,
            'password' => 'nullable|string|min:8',
        ]);

        $coordinator->update(collect($validated)->except('password')->toArray());

        if ($request->filled('password')) {
            $coordinator->update(['password' => Hash::make($request->password)]);
        }

        return response()->json(['message' => 'Koordinatör güncellendi.', 'user' => $coordinator]);
    }

    public function destroy(User $coordinator)
    {
        if (!$coordinator->hasRole('coordinator')) {
            return response()->json(['message' => 'Geçersiz işlem.'], 403);
        }

        $coordinator->delete();
        return response()->json(['message' => 'Koordinatör sistemden silindi.']);
    }
}
