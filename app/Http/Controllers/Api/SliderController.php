<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    public function index()
    {
        return response()->json(Slider::orderBy('order_priority')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url' => 'nullable|url',
            'order_priority' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('sliders', 'public');
            $validated['image_path'] = $path;
        }

        $slider = Slider::create($validated);
        return response()->json($slider, 201);
    }

    public function show(Slider $slider)
    {
        return response()->json($slider);
    }

    public function update(Request $request, $id)
    {
        $slider = Slider::findOrFail($id);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url' => 'nullable|url',
            'order_priority' => 'integer',
            'is_active' => 'boolean'
        ]);

        if ($request->hasFile('image')) {
            if ($slider->image_path) {
                Storage::disk('public')->delete($slider->image_path);
            }
            $path = $request->file('image')->store('sliders', 'public');
            $validated['image_path'] = $path;
        }

        $slider->update($validated);
        return response()->json($slider);
    }

    public function destroy(Slider $slider)
    {
        if ($slider->image_path) {
            Storage::disk('public')->delete($slider->image_path);
        }
        $slider->delete();
        return response()->json(null, 204);
    }
}
