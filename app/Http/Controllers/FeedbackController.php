<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Faaliyet için geri bildirim gönder
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $feedback = Feedback::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'activity_id' => $validated['activity_id']
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment']
            ]
        );

        return response()->json([
            'message' => 'Değerlendirme başarıyla gönderildi.',
            'feedback' => $feedback
        ]);
    }
}
