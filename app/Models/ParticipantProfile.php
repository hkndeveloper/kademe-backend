<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParticipantProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'public_id', 'tc_no', 'phone', 'address', 'university', 'department', 'class',
        'credits', 'status', 'digital_cv', 'age', 'hometown', 'period',
        'is_graduated', 'graduated_at', 'graduation_certificate_id',
        // Blacklist alanları
        'blacklisted_at', 'blacklist_reason',
        // Mezuniyet alanları
        'graduation_status', 'graduated_project_id', 'graduation_reason',
        // Dijital CV alanları
        'cv_uuid', 'public_cv',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($profile) {
            if (!$profile->public_id) {
                $profile->public_id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $casts = [
        'digital_cv' => 'array',
        'credits' => 'integer',
        'is_graduated' => 'boolean',
        'graduated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id', 'user_id');
    }
}
