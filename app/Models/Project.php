<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'location', 'capacity', 'logo', 'is_active',
        'application_deadline', 'format', 'period', 'sub_description', 'timeline', 'documents'
    ];

    protected $casts = [
        'timeline' => 'array',
        'documents' => 'array',
        'is_active' => 'boolean',
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
