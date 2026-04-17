<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Activity;
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

    /**
     * Faaliyetin geri bildirimlerini listele
     */
    public function index(Activity $activity)
    {
        $feedbacks = Feedback::where('activity_id', $activity->id)
            ->with('user')
            ->latest()
            ->get();
            
        return response()->json($feedbacks);
    }

    /**
     * Bekleyen (yapılmamış) değerlendirmeleri kontrol et
     */
    public function checkPending()
    {
        $user = auth()->user();
        
        $pending = \Illuminate\Support\Facades\DB::table('attendances')
            ->join('activities', 'attendances.activity_id', '=', 'activities.id')
            ->leftJoin('feedback', function ($join) use ($user) {
                $join->on('activities.id', '=', 'feedback.activity_id')
                     ->where('feedback.user_id', '=', $user->id);
            })
            ->where('attendances.user_id', $user->id)
            ->where('attendances.status', 'attended')
            ->whereNull('feedback.id')
            ->select('activities.id', 'activities.name', 'activities.start_time')
            ->first();

        return response()->json($pending);
    }
}
