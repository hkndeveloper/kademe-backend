<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'user_id',
        'rating',
        'content'
    ];

    protected $casts = [
        'rating' => 'integer'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
