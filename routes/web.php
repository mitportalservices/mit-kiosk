<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Visitor/Public Routes
Route::get('/', [VisitorController::class, 'index'])->name('visitor.kiosk');
Route::get('/mobile', function () {
    return redirect('/mobile/index.html');
});
Route::get('/panorama/{waypoint}', [VisitorController::class, 'panorama'])->name('visitor.panorama');
Route::get('/kiosk/queue', [VisitorController::class, 'queueAllWindow'])->name('visitor.queue.all');
Route::get('/kiosk/queue/{waypoint}', [VisitorController::class, 'queueWindow'])->name('visitor.queue');

// Auth Flow
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'processLogin'])->middleware('throttle:60,1');
Route::get('/otp', [AuthController::class, 'showOTP'])->name('auth.otp');
Route::post('/otp', [AuthController::class, 'verifyOTP'])->middleware('throttle:60,1');
Route::post('/otp/resend', [AuthController::class, 'resendOTP'])->name('auth.otp.resend')->middleware('throttle:60,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Auth Required)
Route::prefix('admin')->middleware(['auth'])->group(function () {
    
    // Shared Dashboard (Available to both Admin and Faculty)
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    /** --- ADMIN ONLY ROUTES --- */
    Route::middleware(['role:admin'])->group(function () {
        // Waypoint Management
        Route::get('/waypoints', [AdminController::class, 'waypoints'])->name('admin.waypoints');
        Route::get('/waypoints/create', [AdminController::class, 'createWaypoint'])->name('admin.waypoints.create');
        Route::post('/waypoints/store', [AdminController::class, 'storeWaypoint'])->name('admin.waypoints.store');
        Route::get('/waypoints/edit/{waypoint}', [AdminController::class, 'editWaypoint'])->name('admin.waypoints.edit');
        Route::post('/waypoints/update/{waypoint}', [AdminController::class, 'updateWaypoint'])->name('admin.waypoints.update');
        Route::delete('/waypoints/delete/{waypoint}', [AdminController::class, 'destroyWaypoint'])->name('admin.waypoints.destroy');

        // Account / Staff Access Management
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/users/store', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/users/edit/{user}', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::post('/users/update/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/users/delete/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');

        // Appointments
        Route::get('/appointments', [AdminController::class, 'appointments'])->name('admin.appointments');
        Route::get('/appointments/export', [AdminController::class, 'exportAppointments'])->name('admin.appointments.export');
        Route::post('/appointments/{appointmentRequest}/accept', [AdminController::class, 'acceptAppointment'])->name('admin.appointments.accept');
        Route::post('/appointments/{appointmentRequest}/decline', [AdminController::class, 'declineAppointment'])->name('admin.appointments.decline');
        Route::delete('/appointments/{appointmentRequest}', [AdminController::class, 'destroyAppointment'])->name('admin.appointments.destroy');

        // Consultations
        Route::get('/consultations', [AdminController::class, 'consultations'])->name('admin.consultations');
        Route::get('/consultations/export', [AdminController::class, 'exportConsultations'])->name('admin.consultations.export');
        Route::post('/consultations/{consultation}/start', [AdminController::class, 'startConsultation'])->name('admin.consultations.start');
        Route::post('/consultations/{consultation}/finish', [AdminController::class, 'finishConsultation'])->name('admin.consultations.finish');
        Route::delete('/consultations/{consultation}', [AdminController::class, 'destroyConsultation'])->name('admin.consultations.destroy');

        // Feedbacks
        Route::get('/feedbacks', [AdminController::class, 'feedbacks'])->name('admin.feedbacks');
        Route::get('/feedbacks/export', [AdminController::class, 'exportFeedbacks'])->name('admin.feedbacks.export');
        Route::put('/feedbacks/{feedback}', [AdminController::class, 'updateFeedback'])->name('admin.feedbacks.update');
        Route::delete('/feedbacks/{feedback}', [AdminController::class, 'destroyFeedback'])->name('admin.feedbacks.destroy');

        // Settings
        Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
        Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
        Route::post('/settings/test-email', [AdminController::class, 'testEmail'])->name('admin.settings.test_email');

        // Activity Logs
        Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('admin.activity_logs');
        Route::get('/activity-logs/export', [AdminController::class, 'exportActivityLogs'])->name('admin.activity_logs.export');
    });

});
