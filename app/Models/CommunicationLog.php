<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'recipient',
        'content',
        'status',
        'provider'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
