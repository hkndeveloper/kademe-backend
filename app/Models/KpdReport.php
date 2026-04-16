<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpdReport extends Model
{
    protected $fillable = ['user_id', 'title', 'file_path', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
