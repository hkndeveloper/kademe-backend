<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'name', 'description', 'type', 'room_name', 'start_time', 'end_time',
        'latitude', 'longitude', 'radius', 'qr_code_secret', 'google_calendar_event_id',
        'google_calendar_last_synced_at', 'credit_loss_amount', 'is_active', 'is_pinned'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
        'is_pinned' => 'boolean',
        'google_calendar_last_synced_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Zaman bazlı dinamik QR kodu oluşturur.
     * QR her 30 saniyede bir değişir.
     */
    public function getDynamicQrSecret($timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        $interval = 30; // 30 saniye geçerlilik
        $timeSlot = floor($timestamp / $interval);
        
        // Activity ID + APP_KEY + Zaman Dilimi birleşimiyle hash oluşturulur
        return hash_hmac('sha256', $this->id . $timeSlot, config('app.key'));
    }
}
