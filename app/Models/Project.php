<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'location', 'capacity', 'logo', 'is_active'];

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
