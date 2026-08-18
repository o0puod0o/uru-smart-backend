<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MockSsoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\ResearchController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\PatentController;
use App\Http\Controllers\Api\AwardController;
use App\Http\Controllers\Api\AcademicController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\LecturerController;
use App\Http\Controllers\Api\ProceedingController;
use App\Http\Controllers\Api\HspController;
use App\Http\Controllers\Api\WorkexController;
use App\Http\Controllers\Api\BoardexController;
use App\Http\Controllers\Api\EducationController;
use App\Http\Controllers\Api\InterestController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\ExpertiseController;
use App\Http\Controllers\Api\ProfileSearchController;
use App\Http\Controllers\NotificationSettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushTokenController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\EntityFileController;
use App\Http\Controllers\Api\AdminUserApiController;
use App\Http\Controllers\Api\ExternalProfileController;
use App\Http\Controllers\Api\LrdProjectController;
use App\Http\Controllers\Api\LrdProposalController;
use App\Http\Controllers\Api\LrdEducationController;
use App\Http\Controllers\Api\LrdPaperController;
use App\Http\Controllers\Api\LrdReferenceController;
use App\Http\Controllers\Api\LrdResearchController;


// Health check
Route::get('ping', function () {
    return response()->json(['pong' => true]);
});

if (config('sso.mock_routes')) {
    Route::prefix('mock-sso')->group(function () {
        Route::post('oauth/token', [MockSsoController::class, 'token']);
        Route::get('api/userinfo', [MockSsoController::class, 'userinfo']);
        Route::get('experts/{identifier}', [MockSsoController::class, 'expert']);
    });
}


// ===== PUBLIC ROUTES (ไม่ต้อง auth) =====
Route::get('profile/{id}', [ProfileController::class, 'show']);
Route::get('profile/{id}/pdf', [ProfileController::class, 'pdf']);

// ===== AUTHENTICATION =====
Route::prefix('auth')->group(function () {
    // POST /api/auth/login - Rate limited 5 requests/minute
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('sso-token', [AuthController::class, 'loginWithSsoToken'])->middleware('throttle:10,1');
    // POST /api/auth/logout - ต้อง auth:sanctum
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
});


// ===== PROTECTED ROUTES (ต้อง auth:sanctum) =====
Route::middleware('auth:sanctum')->group(function () {
    // Profile management
    Route::get('me', [ProfileController::class, 'me']);
    Route::get('me/external-profile', [ExternalProfileController::class, 'me']);
    Route::put('me', [ProfileController::class, 'update']);
    Route::post('me/photo', [ProfileController::class, 'uploadPhoto']);
    Route::delete('me/photo', [ProfileController::class, 'deletePhoto']);
    Route::post('push-token', [PushTokenController::class, 'store']);
    Route::delete('push-token', [PushTokenController::class, 'destroy']);
    Route::put('notification-settings', [NotificationSettingController::class, 'update']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

    // Chat / Chatbot
    Route::post('chat', [ChatController::class, 'send']);

    // Announcements & Search
    Route::get('announcements', [AnnouncementController::class, 'index']);
    Route::get('profile-search', [ProfileSearchController::class, 'index']);

    // Quick reference endpoints
    Route::get('prefixes', [ReferenceController::class, 'prefixes']);
    Route::get('positions', [ReferenceController::class, 'positions']);
    Route::get('lines', [ReferenceController::class, 'lines']);
    Route::get('main-units', [ReferenceController::class, 'mainUnits']);
    Route::get('sub-units', [ReferenceController::class, 'subUnits']);

    // Reference data - grouped under /api/ref
    Route::prefix('ref')->group(function () {
        Route::get('profile-options', [ReferenceController::class, 'profileOptions']);
        Route::get('search-options', [ReferenceController::class, 'searchOptions']);
        Route::get('search-fields', [ReferenceController::class, 'searchFields']);
        Route::get('expertise-options', [ReferenceController::class, 'expertiseOptions']);
        Route::get('interest-options', [ReferenceController::class, 'interestOptions']);
        Route::get('research-types', [ReferenceController::class, 'researchTypes']);
        Route::get('research-levels', [ReferenceController::class, 'researchLevels']);
        Route::get('research-pmu-types', [ReferenceController::class, 'researchPmuTypes']);
        Route::get('journal-types', [ReferenceController::class, 'journalTypes']);
        Route::get('degrees', [ReferenceController::class, 'degrees']);
        Route::get('departments', [ReferenceController::class, 'departments']);
        Route::get('expertise-groups', [ReferenceController::class, 'expertiseGroups']);
    });

    // RESTful resource routes
    // GET /resources, POST /resources, GET /resources/{id}, PUT /resources/{id}, DELETE /resources/{id}
    Route::apiResource('researches', ResearchController::class);
    Route::apiResource('journals', JournalController::class);
    Route::apiResource('books', BookController::class);
    Route::apiResource('patents', PatentController::class);
    Route::apiResource('awards', AwardController::class);
    Route::apiResource('academics', AcademicController::class);
    Route::apiResource('trainings', TrainingController::class);
    Route::apiResource('lecturers', LecturerController::class);
    Route::apiResource('proceedings', ProceedingController::class);
    Route::apiResource('hsps', HspController::class);
    Route::apiResource('workexes', WorkexController::class);
    Route::apiResource('boardexes', BoardexController::class);
    Route::apiResource('educations', EducationController::class);
    Route::apiResource('interests', InterestController::class);
    Route::apiResource('expertises', ExpertiseController::class);
    Route::apiResource('proposals', ProposalController::class);
    Route::apiResource('reports', ReportController::class);
    Route::prefix('lrd')->name('lrd.')->group(function () {
        Route::get('facultys', [LrdReferenceController::class, 'facultys']);
        Route::get('branchs', [LrdReferenceController::class, 'branchs']);
        Route::get('paperindexs', [LrdReferenceController::class, 'paperindexs']);
        Route::apiResource('educations', LrdEducationController::class);
        Route::apiResource('papers', LrdPaperController::class);
        Route::apiResource('projects', LrdProjectController::class);
        Route::apiResource('researchs', LrdResearchController::class);
        Route::apiResource('proposals', LrdProposalController::class);
    });
    Route::get('files', [EntityFileController::class, 'index']);
    Route::post('files', [EntityFileController::class, 'store']);
    Route::get('files/{file}/download', [EntityFileController::class, 'download']);
    Route::delete('files/{file}', [EntityFileController::class, 'destroy']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('users', [AdminUserApiController::class, 'index']);
        Route::get('users/{user}', [AdminUserApiController::class, 'show']);
        Route::put('users/{user}', [AdminUserApiController::class, 'update']);
        Route::patch('users/{user}/role', [AdminUserApiController::class, 'role']);
        Route::delete('users/{user}', [AdminUserApiController::class, 'destroy']);
    });
});
