<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'link_url',
        'order_priority',
        'is_active'
    ];
}
