<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Tüm kontrollerden önce çalışır. Eğer Super Admin ise her şeye izin ver.
     */
    public function before(User $user, $ability)
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }
    }

    /**
     * Kullanıcı bu projeyi yönetebilir mi? (Projenin ana ayarları)
     */
    public function manageProject(User $user, ?Project $project = null): bool
    {
        return $this->checkProjectAccess($user, $project, 'manage_project');
    }

    /**
     * Kullanıcı bu projeye ait malzemeleri yükleyebilir/silebilir mi?
     */
    public function uploadMaterials(User $user, ?Project $project = null): bool
    {
        return $this->checkProjectAccess($user, $project, 'upload_materials');
    }

    /**
     * Kullanıcı bu proje için yoklama alabilir mi?
     */
    public function takeAttendance(User $user, ?Project $project = null): bool
    {
        return $this->checkProjectAccess($user, $project, 'take_attendance');
    }

    /**
     * Kullanıcı bu proje için başvuruları yönetebilir mi?
     */
    public function evaluateApplications(User $user, ?Project $project = null): bool
    {
        return $this->checkProjectAccess($user, $project, 'evaluate_applications');
    }

    /**
     * Kullanıcı bu proje kapsamında SMS atabilir mi?
     */
    public function sendSms(User $user, ?Project $project = null): bool
    {
        return $this->checkProjectAccess($user, $project, 'send_sms_email');
    }

    /**
     * Yardımcı metot: Kullanıcının hem ilgili izne (permission) sahip olduğunu 
     * hem de bu projeye atanmış bir koordinatör/yetkili olduğunu doğrular.
     */
    private function checkProjectAccess(User $user, ?Project $project, string $permissionName): bool
    {
        // Eğer proje belli değilse (Sadece kendi atandığı projeleri listelemeye mi çalışıyor diye)
        // en azından ilgili izni olup olmadığına bakabiliriz.
        if (!$project) {
            return $user->hasPermissionTo($permissionName);
        }

        // 1. Kullanıcı bu projeye atanmış mı?
        $isAssigned = collect($user->coordinatedProjects)->pluck('id')->contains($project->id);

        // 2. Kullanıcının Spatie tarafında bu spesifik eylem için izni var mı?
        $hasPermission = $user->hasPermissionTo($permissionName);

        return $isAssigned && $hasPermission;
    }
}
