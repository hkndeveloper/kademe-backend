<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMaterial extends Model
{
    protected $fillable = [
        'project_id',
        'uploaded_by',
        'title',
        'description',
        'type',
        'content',
        'file_path',
        'file_type',
        'file_size',
        'is_public'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
