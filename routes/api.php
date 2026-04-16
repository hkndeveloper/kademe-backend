<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ParticipantController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\ActivityFeedbackController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\RejectionCriteriaController;
use App\Http\Controllers\Api\BadgeTierController;

use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);

// Genel Test Maili Rotası (Giriş gerektirmez, parametre alır)
Route::get('/test-mail', function(\Illuminate\Http\Request $request) {
    $email = $request->query('email');
    if (!$email) return response()->json(['message' => 'Lütfen bir email adresi belirtin. Örn: /api/test-mail?email=test@mail.com'], 400);
    
    $commService = app(\App\Services\CommunicationService::class);
    $commService->sendEmail(
        null, // null geçerek FK hatasını önlüyoruz
        $email, 
        'KADEME Sistem Testi', 
        'Bu mesaj, SMTP ayarlarınızın çalıştığını doğrulamak amacıyla gönderilmiştir. Tebrikler!'
    );
    
    return response()->json(['message' => 'Test maili ' . $email . ' adresine gönderildi. Lütfen gelen kutunuzu (ve spam klasörünü) kontrol edin.']);
});
Route::get('/public-stats', [ProjectController::class, 'getPublicStats']); // Genel istatistikler
Route::get('/projects', [ProjectController::class, 'index']); // Public projeler listesi
Route::get('/projects/{project}', [ProjectController::class, 'show']); // Public proje detayı
Route::get('/cv/{uuid}', [\App\Http\Controllers\Api\CVController::class, 'show']); // Public Dijital CV
Route::get('/activities', [ActivityController::class, 'index']); // Public faaliyetler listesi
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']); // Public bülten aboneliği
Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'send']); // Public iletişim formu

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/forget-me', [AuthController::class, 'forgetMe']); // KVKK Veri Silme

    // 2FA (Section 11.12)
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable']);
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify']);
    Route::get('/2fa/status', [TwoFactorController::class, 'status']);

    // Admin & Coordinator Routes
    Route::get('/admin/stats', [\App\Http\Controllers\Api\AdminController::class, 'getStats']);
    Route::get('/admin/visual-analytics', [\App\Http\Controllers\Api\AdminController::class, 'getVisualAnalytics']); // Section 11.16
    Route::get('/admin/coordinators', [\App\Http\Controllers\Api\CoordinatorController::class, 'index']);
    Route::post('/admin/coordinators', [\App\Http\Controllers\Api\CoordinatorController::class, 'store']);
    Route::put('/admin/coordinators/{coordinator}', [\App\Http\Controllers\Api\CoordinatorController::class, 'update']);
    Route::delete('/admin/coordinators/{coordinator}', [\App\Http\Controllers\Api\CoordinatorController::class, 'destroy']);
    Route::post('/admin/users/{userId}/make-alumni', [\App\Http\Controllers\Api\AdminController::class, 'makeAlumni']);
    Route::get('/admin/announcements/logs', [\App\Http\Controllers\Api\AnnouncementController::class, 'getLogs']);
    Route::post('/admin/announcements/bulk-send', [\App\Http\Controllers\Api\AnnouncementController::class, 'bulkSend']);
    Route::apiResource('projects', ProjectController::class)->except(['index', 'show']);
    Route::post('/projects/{project}/bulk-attendance', [ProjectController::class, 'bulkAttendance']);
    Route::apiResource('activities', ActivityController::class)->except(['index']);
    Route::apiResource('participants', ParticipantController::class);
    Route::get('/participants/export/csv', [ParticipantController::class, 'exportCsv']);
    Route::get('/participants/export/excel', [ParticipantController::class, 'exportExcel']);
    Route::get('/participants/export/pdf', [ParticipantController::class, 'exportPdf']);
    Route::post('/participants/{participant}/blacklist', [ParticipantController::class, 'addToBlacklist']);
    Route::post('/participants/{participant}/unblacklist', [ParticipantController::class, 'removeFromBlacklist']);
    Route::get('/participants/blacklisted', [ParticipantController::class, 'getBlacklisted']);
    
    // Graduation & Alumni System (Section 15)
    Route::post('/participants/{participant}/graduation-status', [ParticipantController::class, 'updateGraduationStatus']);
    Route::get('/participants/alumni', [ParticipantController::class, 'getAlumni']);

    // Newsletter System (Section 11.19)
    Route::get('/newsletters', [NewsletterController::class, 'index']);
    Route::post('/newsletters/send', [NewsletterController::class, 'send']);
    Route::post('/newsletters/{email}/unsubscribe', [NewsletterController::class, 'unsubscribe']);
    
    // Credit System (Section 6.3, 11.2, 11.17)
    Route::get('/credits/risk-report', [CreditController::class, 'getRiskReport']);
    Route::post('/credits/adjust', [CreditController::class, 'adjustCredits']);
    Route::post('/credits/reset', [CreditController::class, 'resetCredits']);
    Route::post('/credits/check-alert', [CreditController::class, 'checkAndAlert']);
    Route::post('/settings/bulk', [SettingController::class, 'bulkUpdate']);
    Route::apiResource('settings', SettingController::class);
    
    // Application System (Başvuru)
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::put('/applications/{application}/status', [ApplicationController::class, 'updateStatus']);
    Route::post('/applications/{projectId}/invite-next', [ApplicationController::class, 'inviteNextFromWaitlist']);

    // Automatic Rejection Criteria (Section 11.9)
    Route::get('/projects/{project}/rejection-criteria', [RejectionCriteriaController::class, 'index']);
    Route::post('/projects/{project}/rejection-criteria', [RejectionCriteriaController::class, 'store']);
    Route::put('/rejection-criteria/{criterion}', [RejectionCriteriaController::class, 'update']);
    Route::delete('/rejection-criteria/{criterion}', [RejectionCriteriaController::class, 'destroy']);

    // Certificate System
    Route::get('/certificates/projects/{project}/users/{user}', [\App\Http\Controllers\Api\CertificateController::class, 'generate']);
    
    // Project Materials (Gizli Alan)
    Route::get('/projects/{project}/materials', [\App\Http\Controllers\Api\ProjectMaterialController::class, 'index']);
    Route::post('/projects/{project}/materials', [\App\Http\Controllers\Api\ProjectMaterialController::class, 'store']);
    Route::get('/materials/{id}/download', [\App\Http\Controllers\Api\ProjectMaterialController::class, 'download']);
    Route::delete('/materials/{id}', [\App\Http\Controllers\Api\ProjectMaterialController::class, 'destroy']);
    
    // Forum (Madde 11.18)
    Route::get('/projects/{project}/forum', [\App\Http\Controllers\Api\ForumController::class, 'index']);
    Route::post('/projects/{project}/forum', [\App\Http\Controllers\Api\ForumController::class, 'store']);
    
    // Attendance Routes
    Route::post('/activities/{activity}/refresh-qr', [ActivityController::class, 'refreshQRCode']);
    Route::get('/activities/{activity}/dynamic-qr', [ActivityController::class, 'getDynamicQr']);
    Route::get('/activities/{activity}/ics', [ActivityController::class, 'exportIcs']); // ICS export
    Route::get('/calendar/google/auth-url', [GoogleCalendarController::class, 'authUrl']);
    Route::get('/calendar/google/status', [GoogleCalendarController::class, 'status']);
    Route::post('/calendar/google/sync-all', [GoogleCalendarController::class, 'syncAll']);
    Route::post('/calendar/google/sync-from-google', [GoogleCalendarController::class, 'syncFromGoogle']);
    Route::post('/attendances', [AttendanceController::class, 'store']);
    Route::post('/attendances/manual', [AttendanceController::class, 'manualStore']);
    Route::post('/activities/{activity}/process-absences', [AttendanceController::class, 'processAbsences']);

    // Activity Feedback (Section 11.14)
    Route::post('/activities/{activity}/feedback', [ActivityFeedbackController::class, 'store']);
    Route::get('/activities/{activity}/feedback', [ActivityFeedbackController::class, 'index']);
    Route::get('/feedback/pending', [ActivityFeedbackController::class, 'checkPending']);
    
    // Admin KPD Report Management
    Route::get('/admin/kpd-reports', [\App\Http\Controllers\Api\AdminKpdReportController::class, 'index']);
    Route::post('/admin/kpd-reports', [\App\Http\Controllers\Api\AdminKpdReportController::class, 'store']);
    Route::get('/admin/kpd-reports/{id}/download', [\App\Http\Controllers\Api\AdminKpdReportController::class, 'download']);
    Route::delete('/admin/kpd-reports/{id}', [\App\Http\Controllers\Api\AdminKpdReportController::class, 'destroy']);
    
    // Student Routes
    Route::get('/student/bundle', [\App\Http\Controllers\Api\StudentController::class, 'getBundle']);
    Route::get('/student/badges', [\App\Http\Controllers\Api\StudentController::class, 'getBadges']);
    Route::get('/student/reports', [\App\Http\Controllers\Api\StudentController::class, 'getReports']);
    Route::get('/student/reports/{id}/download', [\App\Http\Controllers\Api\StudentController::class, 'downloadReport']);
    Route::get('/student/certificates', [\App\Http\Controllers\Api\StudentController::class, 'getCertificates']);
    Route::get('/student/cv', [\App\Http\Controllers\Api\CVController::class, 'getMyCv']);
    Route::put('/student/cv', [\App\Http\Controllers\Api\CVController::class, 'update']);

    // Feedback
    Route::post('/feedback', [\App\Http\Controllers\FeedbackController::class, 'store']);

    // Badge Tiers (Gamification)
    Route::get('/badge-tiers', [BadgeTierController::class, 'index']);
    Route::apiResource('admin/badge-tiers', BadgeTierController::class)->except(['index']);
});
