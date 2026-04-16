<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BadgeTier;
use Illuminate\Http\Request;

class BadgeTierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(BadgeTier::orderBy('min_badges', 'asc')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_badges' => 'required|integer|min:0',
            'title' => 'nullable|string|max:255',
            'frame_color' => 'nullable|string|max:255',
            'reward_description' => 'nullable|string'
        ]);

        $tier = BadgeTier::create($validated);
        return response()->json($tier, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BadgeTier $badgeTier)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'min_badges' => 'sometimes|required|integer|min:0',
            'title' => 'nullable|string|max:255',
            'frame_color' => 'nullable|string|max:255',
            'reward_description' => 'nullable|string'
        ]);

        $badgeTier->update($validated);
        return response()->json($badgeTier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BadgeTier $badgeTier)
    {
        $badgeTier->delete();
        return response()->json(null, 204);
    }
}
