<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ParticipantController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\BadgeTierController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CoordinatorController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\CVController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\AdminKpdReportController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\ProjectMaterialController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\KpdAppointmentController;

use Illuminate\Support\Facades\Route;

// ─── Public Routes ────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/public-stats', [PublicController::class, 'getStats']);
Route::get('/public-projects/{id}', [PublicController::class, 'getProjectDetails']);
Route::get('/public-home', [PublicController::class, 'getHomeContent']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);
Route::get('/cv/{uuid}', [CVController::class, 'show']);
Route::get('/activities', [ActivityController::class, 'index']);
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::post('/contact', [ContactController::class, 'send']);

// ─── Protected Routes ─────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Kullanıcı bilgisi ve oturum
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/forget-me', [AuthController::class, 'forgetMe']);

    // 2FA (Section 11.12) — Şu an SMS/Mail entegrasyonu aktif değil, route'lar korunuyor
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable']);
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify']);
    Route::get('/2fa/status', [TwoFactorController::class, 'status']);

    // ─── Admin & Koordinatör ──────────────────────────────────────────────────
    Route::get('/admin/stats', [AdminController::class, 'getStats']);
    Route::get('/admin/visual-analytics', [AdminController::class, 'getVisualAnalytics']);
    Route::get('/admin/coordinators', [CoordinatorController::class, 'index']);
    Route::post('/admin/coordinators', [CoordinatorController::class, 'store']);
    Route::put('/admin/coordinators/{coordinator}', [CoordinatorController::class, 'update']);
    Route::delete('/admin/coordinators/{coordinator}', [CoordinatorController::class, 'destroy']);
    Route::post('/admin/users/{userId}/make-alumni', [AdminController::class, 'makeAlumni']);
    Route::get('/admin/announcements/logs', [AnnouncementController::class, 'getLogs']);
    Route::post('/admin/announcements/bulk-send', [AnnouncementController::class, 'bulkSend']);
    
    // KPD Randevu Yönetimi
    Route::get('/admin/kpd/appointments', [KpdAppointmentController::class, 'index']);
    Route::put('/admin/kpd/appointments/{id}', [KpdAppointmentController::class, 'update']);

    // Kredi ve Blacklist Yönetimi (Section 14.1)
    Route::get('/admin/blacklist', [AdminController::class, 'getBlacklistedUsers']);
    Route::post('/admin/users/{userId}/adjust-credits', [AdminController::class, 'adjustCredits']);

    Route::apiResource('projects', ProjectController::class)->except(['index', 'show']);
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore']);
    Route::post('/projects/{project}/bulk-attendance', [ProjectController::class, 'bulkAttendance']);

    // Faaliyetler
    Route::apiResource('activities', ActivityController::class)->except(['index']);
    Route::post('/activities/{activity}/restore', [ActivityController::class, 'restore']);

    // ─── Katılımcılar (ÖNEMLİ: özel rotalar apiResource'tan ÖNCE) ────────────
    Route::get('/participants/export/csv', [ParticipantController::class, 'exportCsv']);
    Route::get('/participants/export/excel', [ParticipantController::class, 'exportExcel']);
    Route::get('/participants/export/pdf', [ParticipantController::class, 'exportPdf']);
    Route::get('/participants/blacklisted', [ParticipantController::class, 'getBlacklisted']);
    Route::get('/participants/alumni', [ParticipantController::class, 'getAlumni']);
    Route::apiResource('participants', ParticipantController::class);
    Route::post('/participants/{participant}/blacklist', [ParticipantController::class, 'addToBlacklist']);
    Route::post('/participants/{participant}/unblacklist', [ParticipantController::class, 'removeFromBlacklist']);
    Route::put('/participants/{participant}/credits', [ParticipantController::class, 'updateCredits']);
    Route::post('/participants/{participant}/graduation-status', [ParticipantController::class, 'updateGraduationStatus']);

    // ─── Bülten ───────────────────────────────────────────────────────────────
    Route::get('/newsletters', [NewsletterController::class, 'index']);
    Route::post('/newsletters/send', [NewsletterController::class, 'send']);
    Route::post('/newsletters/{email}/unsubscribe', [NewsletterController::class, 'unsubscribe']);

    // ─── Kredi Sistemi (Controller'lar henüz yok, o sebeple yoruma alındı) ───
    // Route::get('/credits/risk-report', [\App\Http\Controllers\Api\CreditController::class, 'getRiskReport']);
    // Route::post('/credits/adjust', [\App\Http\Controllers\Api\CreditController::class, 'adjustCredits']);
    // Route::post('/credits/reset', [\App\Http\Controllers\Api\CreditController::class, 'resetCredits']);
    // Route::post('/credits/check-alert', [\App\Http\Controllers\Api\CreditController::class, 'checkAndAlert']);

    // Sistem Ayarları
    Route::post('/settings/bulk', [SettingController::class, 'bulkUpdate']);
    Route::apiResource('settings', SettingController::class);

    // ─── Başvuru Sistemi ──────────────────────────────────────────────────────
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::put('/applications/{application}/status', [ApplicationController::class, 'updateStatus']);
    Route::post('/applications/{projectId}/invite-next', [ApplicationController::class, 'inviteNextFromWaitlist']);
    Route::put('/applications/{projectId}/waitlist-order', [ApplicationController::class, 'reorderWaitlist']);

    // ─── Otomatik Eleme Kriterleri (Controller'lar henüz yok, o sebeple yoruma alındı) ───
    // Route::get('/projects/{project}/rejection-criteria', [\App\Http\Controllers\Api\RejectionCriteriaController::class, 'index']);
    // Route::post('/projects/{project}/rejection-criteria', [\App\Http\Controllers\Api\RejectionCriteriaController::class, 'store']);
    // Route::put('/rejection-criteria/{criterion}', [\App\Http\Controllers\Api\RejectionCriteriaController::class, 'update']);
    // Route::delete('/rejection-criteria/{criterion}', [\App\Http\Controllers\Api\RejectionCriteriaController::class, 'destroy']);

    // ─── Sertifika ────────────────────────────────────────────────────────────
    Route::get('/certificates/projects/{project}/users/{user}', [CertificateController::class, 'generate']);

    // ─── Proje Materyalleri ───────────────────────────────────────────────────
    Route::get('/projects/{project}/materials', [ProjectMaterialController::class, 'index']);
    Route::post('/projects/{project}/materials', [ProjectMaterialController::class, 'store']);
    Route::get('/materials/{id}/download', [ProjectMaterialController::class, 'download']);
    Route::delete('/materials/{id}', [ProjectMaterialController::class, 'destroy']);

    // ─── Forum ────────────────────────────────────────────────────────────────
    Route::get('/projects/{project}/forum', [ForumController::class, 'index']);
    Route::post('/projects/{project}/forum', [ForumController::class, 'store']);

    // ─── Yoklama ─────────────────────────────────────────────────────────────
    Route::post('/attendances', [AttendanceController::class, 'store']);
    Route::post('/attendances/manual', [AttendanceController::class, 'manualStore']);
    Route::post('/activities/{activity}/process-absences', [AttendanceController::class, 'processAbsences']);
    Route::post('/activities/{activity}/refresh-qr', [ActivityController::class, 'refreshQRCode']);
    Route::get('/activities/{activity}/dynamic-qr', [ActivityController::class, 'getDynamicQr']);
    Route::get('/activities/{activity}/ics', [ActivityController::class, 'exportIcs']);

    // ─── Google Takvim ────────────────────────────────────────────────────────
    Route::get('/calendar/google/auth-url', [GoogleCalendarController::class, 'authUrl']);
    Route::get('/calendar/google/status', [GoogleCalendarController::class, 'status']);
    Route::post('/calendar/google/sync-all', [GoogleCalendarController::class, 'syncAll']);
    Route::post('/calendar/google/sync-from-google', [GoogleCalendarController::class, 'syncFromGoogle']);

    // ─── Geri Bildirim ────────────────────────────────────────────────────────
    Route::post('/activities/{activity}/feedback', [FeedbackController::class, 'store']);
    Route::get('/activities/{activity}/feedback', [FeedbackController::class, 'index']);
    Route::get('/feedback/pending', [FeedbackController::class, 'checkPending']);

    // ─── KPD Raporları ────────────────────────────────────────────────────────
    Route::get('/admin/kpd-reports', [AdminKpdReportController::class, 'index']);
    Route::post('/admin/kpd-reports', [AdminKpdReportController::class, 'store']);
    Route::get('/admin/kpd-reports/{id}/download', [AdminKpdReportController::class, 'download']);
    Route::delete('/admin/kpd-reports/{id}', [AdminKpdReportController::class, 'destroy']);

    // ─── Öğrenci Rotaları ─────────────────────────────────────────────────────
    Route::get('/student/bundle', [StudentController::class, 'getBundle']);
    Route::get('/student/badges', [StudentController::class, 'getBadges']);
    Route::get('/student/reports', [StudentController::class, 'getReports']);
    Route::get('/student/reports/{id}/download', [StudentController::class, 'downloadReport']);
    Route::get('/student/certificates', [StudentController::class, 'getCertificates']);
    Route::get('/student/cv', [CVController::class, 'getMyCv']);
    Route::put('/student/cv', [CVController::class, 'update']);
    
    // KPD Öğrenci Randevu
    Route::get('/student/kpd/appointments', [KpdAppointmentController::class, 'myAppointments']);
    Route::post('/student/kpd/appointments', [KpdAppointmentController::class, 'store']);
    Route::get('/student/kpd/availability', [KpdAppointmentController::class, 'checkAvailability']);

    // ─── Oyunlaştırma / Rozet Kademeleri ─────────────────────────────────────
    Route::get('/badge-tiers', [BadgeTierController::class, 'index']);
    Route::apiResource('admin/badge-tiers', BadgeTierController::class)->except(['index']);

    // --- Support & Messaging ---
    Route::get('support', [SupportController::class, 'index']);
    Route::get('support/{id}', [SupportController::class, 'show']);
    Route::post('support', [SupportController::class, 'store']);
    Route::post('support/{id}/reply', [SupportController::class, 'reply']);

    // --- Dynamic Permissions & Master User Management ---
    Route::group(['prefix' => 'admin', 'middleware' => ['role:super-admin']], function() {
        // Roles & Permissions
        Route::get('roles-permissions', [RolePermissionController::class, 'index']);
        Route::post('roles', [RolePermissionController::class, 'storeRole']);
        Route::put('roles/{role}/permissions', [RolePermissionController::class, 'syncPermissions']);

        // Master User Management
        Route::get('users', [AdminUserController::class, 'index']);
        Route::put('users/{user}/roles', [AdminUserController::class, 'updateRole']);
        Route::delete('users/{user}', [AdminUserController::class, 'destroy']);
    });
});

