<?php

namespace App\Http\Controllers;

use App\Models\Waypoint;
use App\Models\User;
use App\Models\AppointmentRequest;
use App\Models\Consultation;
use App\Models\Feedback;
use App\Models\ActivityLog;
use App\Models\KioskSetting;
use App\Mail\AppointmentScheduled;
use App\Mail\AppointmentDeclined;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class MobileAdminController extends Controller
{
    private function ensureAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Administrative access required.');
        }
    }

    private function formatUserDisplayName(User $user): string
    {
        return trim(($user->title ? $user->title . ' ' : '') . $user->name);
    }

    private function getDisplayTicket(Consultation $c): string
    {
        $role = optional($c->assignedUser)->role;
        $offset = $role === 'principal' ? 100 : ($role === 'guidance_counselor' ? 200 : 0);
        return str_pad((string)((int)$c->ticket_number + $offset), 3, '0', STR_PAD_LEFT);
    }

    /** --- DASHBOARD --- */
    public function dashboard(Request $request)
    {
        $this->ensureAdmin($request);

        $staffCounts = User::query()
            ->whereIn('role', ['faculty', 'principal', 'guidance_counselor'])
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $waypoints = Waypoint::with('assignedUsers')->get();
        $feedbacks = Feedback::orderByDesc('created_at')->take(5)->get();
        $recentLogs = ActivityLog::orderByDesc('created_at')->take(10)->get();

        return response()->json([
            'counts' => [
                'faculty' => (int) ($staffCounts['faculty'] ?? 0),
                'principal' => (int) ($staffCounts['principal'] ?? 0),
                'guidance_counselor' => (int) ($staffCounts['guidance_counselor'] ?? 0),
                'waypoints' => $waypoints->count(),
                'appointments' => AppointmentRequest::count(),
                'consultations' => Consultation::count(),
                'feedbacks' => Feedback::count(),
            ],
            'waypoints' => $waypoints,
            'recent_feedbacks' => $feedbacks,
            'recent_logs' => $recentLogs,
        ]);
    }

    /** --- WAYPOINTS --- */
    public function waypoints(Request $request)
    {
        $this->ensureAdmin($request);
        $waypoints = Waypoint::with('assignedUsers')->get();
        $staffUsers = User::whereIn('role', ['faculty', 'principal', 'guidance_counselor'])->orderBy('name')->get();
        return response()->json([
            'waypoints' => $waypoints,
            'available_staff' => $staffUsers,
        ]);
    }

    public function storeWaypoint(Request $request)
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location_code' => 'required|string|unique:waypoints',
            'description' => 'required|string',
            'floor' => 'required|string',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->whereIn('role', ['faculty', 'principal', 'guidance_counselor']),
            ],
            'coordinates_x' => 'required|numeric',
            'coordinates_y' => 'required|numeric',
            'image_url' => 'nullable|string|max:2048',
            'pano_url' => 'nullable|string|max:2048',
        ]);

        $assignedUserIds = $validated['assigned_user_ids'] ?? [];
        unset($validated['assigned_user_ids']);

        $waypoint = Waypoint::create($validated);
        $waypoint->assignedUsers()->sync($assignedUserIds);

        ActivityLog::record('add_waypoint', "Admin User Added a New Waypoint - {$waypoint->name}.", null, $request->user());

        $waypoint->load('assignedUsers');
        return response()->json($waypoint, 201);
    }

    public function updateWaypoint(Request $request, Waypoint $waypoint)
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location_code' => 'required|string|unique:waypoints,location_code,'.$waypoint->id,
            'description' => 'required|string',
            'floor' => 'required|string',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->whereIn('role', ['faculty', 'principal', 'guidance_counselor']),
            ],
            'coordinates_x' => 'required|numeric',
            'coordinates_y' => 'required|numeric',
            'image_url' => 'nullable|string|max:2048',
            'pano_url' => 'nullable|string|max:2048',
        ]);

        $assignedUserIds = $validated['assigned_user_ids'] ?? [];
        unset($validated['assigned_user_ids']);

        $waypoint->update($validated);
        $waypoint->assignedUsers()->sync($assignedUserIds);

        ActivityLog::record('update_waypoint', "Admin User Updated a Waypoint - {$waypoint->name}.", null, $request->user());

        $waypoint->load('assignedUsers');
        return response()->json($waypoint);
    }

    public function destroyWaypoint(Request $request, Waypoint $waypoint)
    {
        $this->ensureAdmin($request);
        $name = $waypoint->name;
        $waypoint->delete();

        ActivityLog::record('delete_waypoint', "Admin User Deleted a Waypoint - {$name}.", null, $request->user());

        return response()->json(['ok' => true]);
    }

    /** --- USERS / STAFF --- */
    public function users(Request $request)
    {
        $this->ensureAdmin($request);
        $users = User::all();
        return response()->json($users);
    }

    public function storeUser(Request $request)
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,faculty,principal,guidance_counselor',
            'time_availability' => 'nullable|string|max:100',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $user = User::create($validated);

        $isStaff = in_array($user->role, ['faculty', 'principal', 'guidance_counselor']);
        $label = $isStaff ? "Staff - " . $this->formatUserDisplayName($user) : "User - " . $user->name;
        ActivityLog::record('add_user', "Admin User Added a {$label}.", null, $request->user());

        return response()->json($user, 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'title' => 'nullable|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'role' => 'sometimes|required|in:admin,faculty,principal,guidance_counselor',
            'availability_status' => 'nullable|in:available,away,busy',
            'time_availability' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (empty($validated['availability_status'])) {
            unset($validated['availability_status']);
        }

        $user->update($validated);

        $isStaff = in_array($user->role, ['faculty', 'principal', 'guidance_counselor']);
        $label = $isStaff ? "Staff - " . $this->formatUserDisplayName($user) : "User - " . $user->name;
        ActivityLog::record('update_user', "Admin User Updated a {$label}.", null, $request->user());

        return response()->json($user);
    }

    public function destroyUser(Request $request, User $user)
    {
        $this->ensureAdmin($request);
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        if ($user->isAdmin()) {
            return response()->json(['message' => 'Admin accounts cannot be deleted.'], 422);
        }

        $isStaff = in_array($user->role, ['faculty', 'principal', 'guidance_counselor']);
        $label = $isStaff ? "Staff - " . $this->formatUserDisplayName($user) : "User - " . $user->name;

        $user->delete();

        ActivityLog::record('delete_user', "Admin User Deleted a {$label}.", null, $request->user());

        return response()->json(['ok' => true]);
    }

    /** --- APPOINTMENTS --- */
    public function appointments(Request $request)
    {
        $this->ensureAdmin($request);
        $appointments = AppointmentRequest::with(['targetUser', 'waypoint'])
            ->orderByDesc('created_at')
            ->get();
        return response()->json($appointments);
    }

    public function acceptAppointment(Request $request, AppointmentRequest $appointmentRequest)
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'target_user_id' => 'nullable|exists:users,id',
        ]);

        if (isset($validated['target_user_id'])) {
            $appointmentRequest->target_user_id = $validated['target_user_id'];
        }

        $appointmentRequest->status = 'accepted';
        $appointmentRequest->scheduled_at = $validated['scheduled_at'];
        $appointmentRequest->responded_at = now();
        $appointmentRequest->save();

        $requester = trim(($appointmentRequest->requester_honorific ?? '') . ' ' . ($appointmentRequest->requester_name ?? ''));
        ActivityLog::record('update_appointment', "Admin User Updated an Appointment - {$requester}.", null, $request->user());

        try {
            Mail::to($appointmentRequest->requester_email)->send(new AppointmentScheduled($appointmentRequest));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Email failed: " . $e->getMessage());
        }

        return response()->json($appointmentRequest);
    }

    public function declineAppointment(Request $request, AppointmentRequest $appointmentRequest)
    {
        $this->ensureAdmin($request);
        $appointmentRequest->status = 'declined';
        $appointmentRequest->responded_at = now();
        $appointmentRequest->save();

        $requester = trim(($appointmentRequest->requester_honorific ?? '') . ' ' . ($appointmentRequest->requester_name ?? ''));
        ActivityLog::record('update_appointment', "Admin User Updated an Appointment - {$requester}.", null, $request->user());

        try {
            Mail::to($appointmentRequest->requester_email)->send(new AppointmentDeclined($appointmentRequest));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Email failed: " . $e->getMessage());
        }

        return response()->json($appointmentRequest);
    }

    public function destroyAppointment(Request $request, AppointmentRequest $appointmentRequest)
    {
        $this->ensureAdmin($request);
        $requester = trim(($appointmentRequest->requester_honorific ?? '') . ' ' . ($appointmentRequest->requester_name ?? ''));
        $appointmentRequest->delete();

        ActivityLog::record('delete_appointment', "Admin User Deleted an Appointment - {$requester}.", null, $request->user());

        return response()->json(['ok' => true]);
    }

    /** --- CONSULTATIONS --- */
    public function consultations(Request $request)
    {
        $this->ensureAdmin($request);
        $consultations = Consultation::with(['assignedUser', 'waypoint'])
            ->orderByDesc('created_at')
            ->get();
        return response()->json($consultations);
    }

    public function startConsultation(Request $request, Consultation $consultation)
    {
        $this->ensureAdmin($request);
        $consultation->status = 'in_progress';
        $consultation->started_at = $consultation->started_at ?: now();
        $consultation->save();

        $ticket = $this->getDisplayTicket($consultation);
        ActivityLog::record('update_consultation', "Admin User Updated a Consultation - Ticket #{$ticket}.", null, $request->user());

        return response()->json($consultation);
    }

    public function finishConsultation(Request $request, Consultation $consultation)
    {
        $this->ensureAdmin($request);
        $consultation->status = 'completed';
        $consultation->completed_at = now();
        $consultation->save();

        $ticket = $this->getDisplayTicket($consultation);
        ActivityLog::record('update_consultation', "Admin User Updated a Consultation - Ticket #{$ticket}.", null, $request->user());

        return response()->json($consultation);
    }

    public function destroyConsultation(Request $request, Consultation $consultation)
    {
        $this->ensureAdmin($request);
        $ticket = $this->getDisplayTicket($consultation);
        $consultation->delete();

        ActivityLog::record('delete_consultation', "Admin User Deleted a Consultation - Ticket #{$ticket}.", null, $request->user());

        return response()->json(['ok' => true]);
    }

    /** --- FEEDBACKS --- */
    public function feedbacks(Request $request)
    {
        $this->ensureAdmin($request);
        $feedbacks = Feedback::orderByDesc('created_at')->get();
        return response()->json($feedbacks);
    }

    public function destroyFeedback(Request $request, Feedback $feedback)
    {
        $this->ensureAdmin($request);
        $id = $feedback->id;
        $feedback->delete();

        ActivityLog::record('delete_feedback', "Admin User Deleted a Feedback - ID #{$id}.", null, $request->user());

        return response()->json(['ok' => true]);
    }

    public function updateFeedback(Request $request, Feedback $feedback)
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'rating' => 'required|string',
            'suggestions' => 'nullable|string',
        ]);

        $feedback->update($validated);
        $id = $feedback->id;

        ActivityLog::record('update_feedback', "Admin User Updated a Feedback - ID #{$id}.", null, $request->user());

        return response()->json($feedback);
    }

    /** --- SETTINGS --- */
    public function settings(Request $request)
    {
        $this->ensureAdmin($request);
        return response()->json(KioskSetting::getAllSettings());
    }

    public function updateSettings(Request $request)
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'service_hours_start' => 'required|string',
            'service_hours_end' => 'required|string',
            'service_days' => 'required|string',
            'kiosk_title' => 'required|string',
            'kiosk_status' => 'required|in:online,maintenance,offline',
            'contact_email' => 'nullable|email',
        ]);

        foreach ($validated as $key => $val) {
            KioskSetting::set($key, $val);
        }

        ActivityLog::record('update_service_hours', "Admin User Updated a Service Hours", null, $request->user());

        return response()->json(KioskSetting::getAllSettings());
    }

    public function testEmail(Request $request)
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            Mail::raw('This is a test email sent from the MIT Kiosk Mobile Admin Panel to verify email/SMTP configuration.', function ($msg) use ($validated) {
                $msg->to($validated['email'])
                    ->subject('MIT Kiosk SMTP Test (Mobile Admin)');
            });

            return response()->json(['message' => "Test email successfully dispatched to {$validated['email']}."]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to send test email: ' . $e->getMessage()], 500);
        }
    }

    /** --- ACTIVITY LOGS --- */
    public function activityLogs(Request $request)
    {
        $this->ensureAdmin($request);

        $query = ActivityLog::orderByDesc('created_at');

        $role = $request->get('role');
        if ($role === 'admin') {
            $query->where('user_role', 'admin');
        } elseif ($role === 'staff') {
            $query->whereIn('user_role', ['faculty', 'principal', 'guidance_counselor']);
        }

        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        return response()->json($query->take(100)->get());
    }
}
