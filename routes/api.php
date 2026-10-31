<?php

use App\Http\Controllers\WaypointController;
use Illuminate\Support\Facades\Route;

// Public API for Visitor Kiosk
Route::get('/waypoints', [WaypointController::class, 'index']);

Route::prefix('mobile')->group(function () {
    Route::post('/login', [\App\Http\Controllers\MobileAuthController::class, 'login'])->middleware('throttle:60,1');
    Route::post('/forgot-password', [\App\Http\Controllers\MobileAuthController::class, 'forgotPassword'])->middleware('throttle:20,1');
    Route::post('/logout', [\App\Http\Controllers\MobileAuthController::class, 'logout'])->middleware('api.token');
    Route::get('/me', [\App\Http\Controllers\MobileAuthController::class, 'me'])->middleware('api.token');

    // Staff Appointments & Consultations
    Route::get('/appointments', [\App\Http\Controllers\MobileAppointmentsController::class, 'index'])->middleware('api.token');
    Route::post('/appointments/{appointmentRequest}/accept', [\App\Http\Controllers\MobileAppointmentsController::class, 'accept'])->middleware('api.token');
    Route::post('/appointments/{appointmentRequest}/decline', [\App\Http\Controllers\MobileAppointmentsController::class, 'decline'])->middleware('api.token');

    Route::get('/consultations', [\App\Http\Controllers\MobileConsultationsController::class, 'index'])->middleware('api.token');
    Route::post('/consultations/{consultation}/start', [\App\Http\Controllers\MobileConsultationsController::class, 'start'])->middleware('api.token');
    Route::post('/consultations/{consultation}/finish', [\App\Http\Controllers\MobileConsultationsController::class, 'finish'])->middleware('api.token');

    Route::post('/profile/availability', [\App\Http\Controllers\MobileProfileController::class, 'updateAvailability'])->middleware('api.token');
    Route::post('/profile/password', [\App\Http\Controllers\MobileProfileController::class, 'changePassword'])->middleware('api.token');

    // Admin Mobile Panel API
    Route::prefix('admin')->middleware('api.token')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\MobileAdminController::class, 'dashboard']);

        // Waypoints
        Route::get('/waypoints', [\App\Http\Controllers\MobileAdminController::class, 'waypoints']);
        Route::post('/waypoints', [\App\Http\Controllers\MobileAdminController::class, 'storeWaypoint']);
        Route::post('/waypoints/{waypoint}', [\App\Http\Controllers\MobileAdminController::class, 'updateWaypoint']);
        Route::delete('/waypoints/{waypoint}', [\App\Http\Controllers\MobileAdminController::class, 'destroyWaypoint']);

        // Accounts / Users
        Route::get('/users', [\App\Http\Controllers\MobileAdminController::class, 'users']);
        Route::post('/users', [\App\Http\Controllers\MobileAdminController::class, 'storeUser']);
        Route::post('/users/{user}', [\App\Http\Controllers\MobileAdminController::class, 'updateUser']);
        Route::put('/users/{user}', [\App\Http\Controllers\MobileAdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [\App\Http\Controllers\MobileAdminController::class, 'destroyUser']);

        // Appointments
        Route::get('/appointments', [\App\Http\Controllers\MobileAdminController::class, 'appointments']);
        Route::post('/appointments/{appointmentRequest}/accept', [\App\Http\Controllers\MobileAdminController::class, 'acceptAppointment']);
        Route::post('/appointments/{appointmentRequest}/decline', [\App\Http\Controllers\MobileAdminController::class, 'declineAppointment']);
        Route::delete('/appointments/{appointmentRequest}', [\App\Http\Controllers\MobileAdminController::class, 'destroyAppointment']);

        // Consultations
        Route::get('/consultations', [\App\Http\Controllers\MobileAdminController::class, 'consultations']);
        Route::post('/consultations/{consultation}/start', [\App\Http\Controllers\MobileAdminController::class, 'startConsultation']);
        Route::post('/consultations/{consultation}/finish', [\App\Http\Controllers\MobileAdminController::class, 'finishConsultation']);
        Route::delete('/consultations/{consultation}', [\App\Http\Controllers\MobileAdminController::class, 'destroyConsultation']);

        // Feedbacks
        Route::get('/feedbacks', [\App\Http\Controllers\MobileAdminController::class, 'feedbacks']);
        Route::put('/feedbacks/{feedback}', [\App\Http\Controllers\MobileAdminController::class, 'updateFeedback']);
        Route::delete('/feedbacks/{feedback}', [\App\Http\Controllers\MobileAdminController::class, 'destroyFeedback']);

        // Settings / Service Hours
        Route::get('/settings', [\App\Http\Controllers\MobileAdminController::class, 'settings']);
        Route::post('/settings', [\App\Http\Controllers\MobileAdminController::class, 'updateSettings']);
        Route::post('/settings/test-email', [\App\Http\Controllers\MobileAdminController::class, 'testEmail']);

        // Activity Logs
        Route::get('/activity-logs', [\App\Http\Controllers\MobileAdminController::class, 'activityLogs']);
    });
});

Route::prefix('kiosk')->middleware('throttle:60,1')->group(function () {
    Route::post('/consultations', [\App\Http\Controllers\KioskConsultationsController::class, 'store']);
    Route::post('/appointments', [\App\Http\Controllers\KioskAppointmentsController::class, 'store']);
    Route::post('/feedback', [\App\Http\Controllers\KioskFeedbackController::class, 'store']);
    Route::post('/print', [\App\Http\Controllers\KioskPrintController::class, 'ticket']);
    Route::options('/print', function () {
        return response('', 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
    });
    Route::get('/queue', [\App\Http\Controllers\KioskQueueController::class, 'index']);
    Route::get('/queue/{waypoint}', [\App\Http\Controllers\KioskQueueController::class, 'show']);
    Route::get('/status', [\App\Http\Controllers\KioskStatusController::class, 'index']);
});

Route::middleware('api.token')->group(function () {
    Route::apiResource('waypoints', WaypointController::class)->except(['index', 'show']);
});
