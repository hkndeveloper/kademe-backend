<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BadgeTier extends Model
{
    protected $fillable = ['name', 'min_badges', 'title', 'frame_color', 'reward_description'];
}
