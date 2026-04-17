<?php

namespace App\Services;

use App\Models\User;
use App\Models\ParticipantProfile;

class ParticipantService
{
    /**
     * Activate a participant: change role to student and update profile
     */
    public function activateParticipant(User $user)
    {
        if (!$user->hasRole('student')) {
            $user->removeRole('guest');
            $user->assignRole('student');
            
            // Profil dönüşümü: Pasiften Aktife geçiş ve 100 kredi ataması
            ParticipantProfile::updateOrCreate(
                ['user_id' => $user->id],
                ['credits' => 100, 'status' => 'active']
            );
        }
    }
}
