<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstagramPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstagramPostController extends Controller
{
    public function index()
    {
        return response()->json(InstagramPost::orderBy('order_priority')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'post_url' => 'nullable|url',
            'caption' => 'nullable|string|max:255',
            'order_priority' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('instagram', 'public');
            $validated['image_path'] = $path;
        }

        $post = InstagramPost::create($validated);
        return response()->json($post, 201);
    }

    public function show(InstagramPost $instagramPost)
    {
        return response()->json($instagramPost);
    }

    public function update(Request $request, $id)
    {
        $post = InstagramPost::findOrFail($id);
        
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'post_url' => 'nullable|url',
            'caption' => 'nullable|string|max:255',
            'order_priority' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $path = $request->file('image')->store('instagram', 'public');
            $validated['image_path'] = $path;
        }

        $post->update($validated);
        return response()->json($post);
    }

    public function destroy(InstagramPost $instagramPost)
    {
        $instagramPost->delete();
        return response()->json(null, 204);
    }
}
