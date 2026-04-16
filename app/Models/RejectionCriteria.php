<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RejectionCriteria extends Model
{
    use HasFactory;

    protected $table = 'rejection_criteria'; // Laravel bunu "rejection_criterias" yanlış tahmin ediyor

    protected $fillable = [
        'project_id',
        'criteria_type',
        'operator',
        'value',
        'rejection_message',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if a participant profile meets this criteria
     */
    public function checkCriteria($participantProfile)
    {
        $value = $participantProfile->{$this->criteria_type} ?? null;

        switch ($this->operator) {
            case 'equals':
                return $value == $this->value;
            case 'not_equals':
                return $value != $this->value;
            case 'greater_than':
                return $value > $this->value;
            case 'less_than':
                return $value < $this->value;
            case 'contains':
                return str_contains($value, $this->value);
            case 'not_contains':
                return !str_contains($value, $this->value);
            default:
                return true;
        }
    }
}
