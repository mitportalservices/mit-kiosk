<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentDeclined;
use App\Mail\AppointmentScheduled;
use App\Models\AppointmentRequest;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MobileAppointmentsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $items = AppointmentRequest::query()
            ->with(['waypoint:id,name', 'targetUser:id,name,role,availability_status,time_availability'])
            ->where('target_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($items->map(function ($a) {
            return [
                'id' => $a->id,
                'target_user_id' => $a->target_user_id,
                'target_user_name' => $a->targetUser?->name,
                'target_user_status' => $a->targetUser?->availability_status,
                'target_user_time_availability' => $a->targetUser?->time_availability,
                'waypoint_id' => $a->waypoint_id,
                'waypoint_name' => $a->waypoint?->name,
                'requester_type' => $a->requester_type,
                'requester_name' => $a->requester_name,
                'grade_level' => $a->grade_level,
                'requester_email' => $a->requester_email,
                'purpose_code' => $a->purpose_code,
                'purpose_other_text' => $a->purpose_other_text,
                'status' => $a->status,
                'scheduled_at' => $a->scheduled_at,
                'responded_at' => $a->responded_at,
                'created_at' => $a->created_at,
            ];
        }));
    }

    public function accept(Request $request, AppointmentRequest $appointmentRequest)
    {
        $user = $request->user();
        if ((int) $appointmentRequest->target_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date',
        ]);

        $appointmentRequest->status = 'accepted';
        $appointmentRequest->scheduled_at = $validated['scheduled_at'];
        $appointmentRequest->responded_at = now();
        $appointmentRequest->save();

        $requester = trim(($appointmentRequest->requester_honorific ?? '') . ' ' . ($appointmentRequest->requester_name ?? ''));
        ActivityLog::record('updated_appointment', "Staff Updated an Appointment - {$requester}.", ['id' => $appointmentRequest->id], $user);

        try {
            Mail::to($appointmentRequest->requester_email)->send(new AppointmentScheduled($appointmentRequest));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Email failed: " . $e->getMessage());
        }

        return response()->json([
            'id' => $appointmentRequest->id,
            'target_user_id' => $appointmentRequest->target_user_id,
            'waypoint_id' => $appointmentRequest->waypoint_id,
            'waypoint_name' => $appointmentRequest->waypoint?->name,
            'requester_type' => $appointmentRequest->requester_type,
            'requester_name' => $appointmentRequest->requester_name,
            'grade_level' => $appointmentRequest->grade_level,
            'requester_email' => $appointmentRequest->requester_email,
            'purpose_code' => $appointmentRequest->purpose_code,
            'purpose_other_text' => $appointmentRequest->purpose_other_text,
            'status' => $appointmentRequest->status,
            'scheduled_at' => $appointmentRequest->scheduled_at,
            'responded_at' => $appointmentRequest->responded_at,
            'created_at' => $appointmentRequest->created_at,
        ]);
    }

    public function decline(Request $request, AppointmentRequest $appointmentRequest)
    {
        $user = $request->user();
        if ((int) $appointmentRequest->target_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $appointmentRequest->status = 'declined';
        $appointmentRequest->responded_at = now();
        $appointmentRequest->save();

        $requester = trim(($appointmentRequest->requester_honorific ?? '') . ' ' . ($appointmentRequest->requester_name ?? ''));
        ActivityLog::record('updated_appointment', "Staff Updated an Appointment - {$requester}.", ['id' => $appointmentRequest->id], $user);

        try {
            Mail::to($appointmentRequest->requester_email)->send(new AppointmentDeclined($appointmentRequest));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Email failed: " . $e->getMessage());
        }

        return response()->json([
            'id' => $appointmentRequest->id,
            'target_user_id' => $appointmentRequest->target_user_id,
            'waypoint_id' => $appointmentRequest->waypoint_id,
            'waypoint_name' => $appointmentRequest->waypoint?->name,
            'requester_type' => $appointmentRequest->requester_type,
            'requester_name' => $appointmentRequest->requester_name,
            'grade_level' => $appointmentRequest->grade_level,
            'requester_email' => $appointmentRequest->requester_email,
            'purpose_code' => $appointmentRequest->purpose_code,
            'purpose_other_text' => $appointmentRequest->purpose_other_text,
            'status' => $appointmentRequest->status,
            'scheduled_at' => $appointmentRequest->scheduled_at,
            'responded_at' => $appointmentRequest->responded_at,
            'created_at' => $appointmentRequest->created_at,
        ]);
    }
}
