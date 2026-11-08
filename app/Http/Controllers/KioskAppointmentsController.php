<?php

namespace App\Http\Controllers;

use App\Models\AppointmentRequest;
use App\Models\Waypoint;
use App\Mail\AppointmentRequested;
use App\Mail\StaffAppointmentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class KioskAppointmentsController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'waypoint_id' => 'nullable|exists:waypoints,id',
            'target_user_id' => 'required|exists:users,id',
            'requester_type' => 'required|in:student,parent,visitor',
            'requester_honorific' => 'nullable|string|max:20',
            'requester_name' => 'required|string|max:255',
            'grade_level' => 'nullable|string|max:20',
            'requester_email' => 'required|email|max:255',
            'purpose_code' => 'required|string|max:100',
            'purpose_other_text' => 'nullable|string|max:1000',
        ]);

        $waypointId = $validated['waypoint_id'] ?? null;
        $targetUserId = (int) $validated['target_user_id'];

        $grade = $validated['grade_level'] ?? null;
        if ($validated['requester_type'] === 'student') {
            if (!$grade) {
                return response()->json(['message' => 'Grade level is required for students.'], 422);
            }
        } else {
            $grade = ucfirst($validated['requester_type']);
        }

        $appointment = AppointmentRequest::create([
            'target_user_id' => $targetUserId,
            'waypoint_id' => $waypointId,
            'requester_type' => $validated['requester_type'],
            'requester_honorific' => $validated['requester_honorific'] ?? null,
            'requester_name' => $validated['requester_name'],
            'grade_level' => $grade,
            'requester_email' => $validated['requester_email'],
            'purpose_code' => $validated['purpose_code'],
            'purpose_other_text' => $validated['purpose_other_text'] ?? null,
            'status' => 'pending',
        ]);

        try {
            Mail::to($appointment->requester_email)->send(new AppointmentRequested($appointment));
        } catch (\Throwable $e) {
            Log::error('Kiosk appointment request email failed: ' . $e->getMessage());
        }

        // Also notify the target staff member by email if they have an email address
        try {
            $appointment->loadMissing('targetUser');
            if ($appointment->targetUser && !empty($appointment->targetUser->email)) {
                Mail::to($appointment->targetUser->email)->send(new StaffAppointmentNotification($appointment));
            }
        } catch (\Throwable $e) {
            Log::error('Staff appointment notification email failed: ' . $e->getMessage());
        }

        return response()->json($appointment, 201);
    }
}
