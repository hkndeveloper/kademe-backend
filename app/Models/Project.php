<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'project_code', 'slug', 'description', 'location', 'capacity', 'logo', 'is_active', 'is_pinned',
        'application_deadline', 'format', 'period', 'sub_description', 'timeline', 'documents'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = \Illuminate\Support\Str::slug($project->name);
            }
        });
    }

    protected $casts = [
        'timeline' => 'array',
        'documents' => 'array',
        'is_active' => 'boolean',
        'is_pinned' => 'boolean',
        'application_deadline' => 'date'
    ];

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function coordinators()
    {
        return $this->belongsToMany(User::class, 'project_coordinator');
    }

    public function materials()
    {
        return $this->hasMany(ProjectMaterial::class);
    }
}
