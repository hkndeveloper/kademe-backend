<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstagramPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'image_path',
        'post_url',
        'caption',
        'order_priority',
        'is_active'
    ];
}
