<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        return response()->json(Faq::orderBy('order_priority')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'order_priority' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        $faq = Faq::create($validated);
        return response()->json($faq, 201);
    }

    public function show(Faq $faq)
    {
        return response()->json($faq);
    }

    public function update(Request $request, Faq $faq)
    {
        $validated = $request->validate([
            'question' => 'string|max:255',
            'answer' => 'string',
            'order_priority' => 'integer',
            'is_active' => 'boolean'
        ]);

        $faq->update($validated);
        return response()->json($faq);
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return response()->json(null, 204);
    }
}
