<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'type',
        'target_type',
        'target_id',
        'sender_id'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
}
