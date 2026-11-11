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
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AdminController extends Controller
{
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

    /** Display Dashboard */
    public function dashboard()
    {
        $waypoints = Waypoint::query()->get();
        $staffCounts = User::query()
            ->whereIn('role', ['faculty', 'principal', 'guidance_counselor'])
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $feedbacks = Feedback::orderByDesc('created_at')->get();
        $recentLogs = ActivityLog::orderByDesc('created_at')->take(6)->get();

        return view('admin.dashboard', compact('waypoints', 'staffCounts', 'feedbacks', 'recentLogs'));
    }

    /** --- WAYPOINT CRUD --- */
    public function waypoints()
    {
        $waypoints = Waypoint::with('assignedUsers')->get();
        return view('admin.waypoints.index', compact('waypoints'));
    }

    public function createWaypoint()
    {
        $staffUsers = User::whereIn('role', ['faculty', 'principal', 'guidance_counselor'])->orderBy('name')->get();
        return view('admin.waypoints.create', compact('staffUsers'));
    }

    public function storeWaypoint(Request $request)
    {
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
            'image_url' => 'nullable|string|max:8192',
            'pano_url' => 'nullable|string|max:8192',
        ]);

        $assignedUserIds = $validated['assigned_user_ids'] ?? [];
        unset($validated['assigned_user_ids']);

        $waypoint = Waypoint::create($validated);
        $waypoint->assignedUsers()->sync($assignedUserIds);

        ActivityLog::record('add_waypoint', "Admin User Added a New Waypoint - {$waypoint->name}.");

        return redirect()->route('admin.waypoints')->with('success', 'Waypoint synced successfully.');
    }

    public function editWaypoint(Waypoint $waypoint)
    {
        $staffUsers = User::whereIn('role', ['faculty', 'principal', 'guidance_counselor'])->orderBy('name')->get();
        $waypoint->load('assignedUsers');
        return view('admin.waypoints.edit', compact('waypoint', 'staffUsers'));
    }

    public function updateWaypoint(Request $request, Waypoint $waypoint)
    {
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
            'image_url' => 'nullable|string|max:8192',
            'pano_url' => 'nullable|string|max:8192',
        ]);

        $assignedUserIds = $validated['assigned_user_ids'] ?? [];
        unset($validated['assigned_user_ids']);

        $waypoint->update($validated);
        $waypoint->assignedUsers()->sync($assignedUserIds);

        ActivityLog::record('update_waypoint', "Admin User Updated a Waypoint - {$waypoint->name}.");

        return redirect()->route('admin.waypoints')->with('success', 'Waypoint updated.');
    }

    public function destroyWaypoint(Waypoint $waypoint)
    {
        $name = $waypoint->name;
        $waypoint->delete();

        ActivityLog::record('delete_waypoint', "Admin User Deleted a Waypoint - {$name}.");

        return back()->with('success', 'Waypoint removed.');
    }

    /** --- USER MANAGEMENT (Accounts) --- */
    public function users()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
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
        ActivityLog::record('add_user', "Admin User Added a {$label}.");

        return redirect()->route('admin.users')->with('success', 'Account created successfully.');
    }

    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,faculty,principal,guidance_counselor',
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
        ActivityLog::record('update_user', "Admin User Updated a {$label}.");

        return redirect()->route('admin.users')->with('success', 'Account updated successfully.');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isAdmin()) {
            return back()->with('error', 'Admin accounts cannot be deleted for safety.');
        }

        $isStaff = in_array($user->role, ['faculty', 'principal', 'guidance_counselor']);
        $label = $isStaff ? "Staff - " . $this->formatUserDisplayName($user) : "User - " . $user->name;

        $user->delete();

        ActivityLog::record('delete_user', "Admin User Deleted a {$label}.");

        return back()->with('success', 'Account removed.');
    }

    /**
     * Helper to apply date filtering and sorting to queries
     */
    protected function filterByDate($query, Request $request, $dateColumn = 'created_at')
    {
        $filter = $request->get('filter', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $sortOrder = $request->get('sort', 'desc');

        switch ($filter) {
            case 'day':
                $query->whereDate($dateColumn, Carbon::today());
                break;
            case 'week':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
                break;
            case 'month':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
                break;
            case 'year':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->startOfYear(),
                    Carbon::now()->endOfYear(),
                ]);
                break;
            case 'custom':
                if ($startDate && $endDate) {
                    $query->whereBetween($dateColumn, [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay(),
                    ]);
                } elseif ($startDate) {
                    $query->where($dateColumn, '>=', Carbon::parse($startDate)->startOfDay());
                } elseif ($endDate) {
                    $query->where($dateColumn, '<=', Carbon::parse($endDate)->endOfDay());
                }
                break;
        }

        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';
        return $query->orderBy($dateColumn, $sortOrder);
    }

    public function appointments(Request $request)
    {
        $query = AppointmentRequest::with(['targetUser', 'waypoint']);
        $this->filterByDate($query, $request, 'created_at');
        $appointments = $query->get();

        return view('admin.appointments.index', compact('appointments'));
    }

    public function exportAppointments(Request $request)
    {
        $query = AppointmentRequest::with(['targetUser', 'waypoint']);
        $this->filterByDate($query, $request, 'created_at');
        $appointments = $query->get();

        $filter = $request->get('filter', 'all');
        $filename = 'appointments_' . $filter . '_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($appointments) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            fputcsv($handle, [
                'ID',
                'Date Created',
                'Time Created',
                'Staff Target',
                'Requester Name',
                'Requester Type',
                'Grade / Level',
                'Requester Email',
                'Purpose of Visit',
                'Purpose Details',
                'Status',
                'Scheduled Date',
                'Scheduled Time',
            ]);

            foreach ($appointments as $a) {
                $target = ($a->targetUser ? ($a->targetUser->title ? $a->targetUser->title . ' ' : '') . $a->targetUser->name : 'N/A');
                $requester = trim(($a->requester_honorific ?? '') . ' ' . ($a->requester_name ?? ''));
                $schedDate = $a->scheduled_at ? Carbon::parse($a->scheduled_at)->format('Y-m-d') : 'Unscheduled';
                $schedTime = $a->scheduled_at ? Carbon::parse($a->scheduled_at)->format('h:i A') : '';

                fputcsv($handle, [
                    $a->id,
                    $a->created_at ? $a->created_at->format('Y-m-d') : '',
                    $a->created_at ? $a->created_at->format('h:i A') : '',
                    $target,
                    $requester,
                    ucfirst($a->requester_type ?? ''),
                    $a->grade_level ?: 'N/A',
                    $a->requester_email,
                    $a->purpose_code,
                    $a->purpose_other_text ?? '',
                    ucfirst($a->status),
                    $schedDate,
                    $schedTime,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function acceptAppointment(Request $request, AppointmentRequest $appointmentRequest)
    {
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
        ActivityLog::record('update_appointment', "Admin User Updated an Appointment - {$requester}.");

        try {
            Mail::to($appointmentRequest->requester_email)->send(new AppointmentScheduled($appointmentRequest));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Appointment accepted email failed: ' . $e->getMessage());
            return back()->with('error', 'Accepted, but failed to send email: ' . $e->getMessage());
        }

        return back()->with('success', 'Appointment request accepted and email sent.');
    }

    public function declineAppointment(Request $request, AppointmentRequest $appointmentRequest)
    {
        if ($appointmentRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be declined.');
        }

        $appointmentRequest->status = 'declined';
        $appointmentRequest->responded_at = now();
        $appointmentRequest->save();

        $requester = trim(($appointmentRequest->requester_honorific ?? '') . ' ' . ($appointmentRequest->requester_name ?? ''));
        ActivityLog::record('update_appointment', "Admin User Updated an Appointment - {$requester}.");

        try {
            Mail::to($appointmentRequest->requester_email)->send(new AppointmentDeclined($appointmentRequest));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Appointment declined email failed: ' . $e->getMessage());
            return back()->with('error', 'Declined, but failed to send email: ' . $e->getMessage());
        }

        return back()->with('success', 'Appointment request declined and email sent.');
    }

    public function destroyAppointment(AppointmentRequest $appointmentRequest)
    {
        $requester = trim(($appointmentRequest->requester_honorific ?? '') . ' ' . ($appointmentRequest->requester_name ?? ''));
        $appointmentRequest->delete();

        ActivityLog::record('delete_appointment', "Admin User Deleted an Appointment - {$requester}.");

        return back()->with('success', 'Appointment removed successfully.');
    }

    // Debug method to test SMTP configuration
    public function testEmail(Request $request)
    {
        $to = $request->input('email', config('mail.from.address'));
        try {
            \Illuminate\Support\Facades\Mail::raw('This is a test email confirming your MIT Kiosk email configuration is working successfully.', function ($message) use ($to) {
                $message->to($to)->subject('MIT Kiosk - SMTP Test Email');
            });
            return back()->with('success', "Test email successfully sent to {$to}!");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Test email failed: ' . $e->getMessage());
            return back()->with('error', 'Test email failed: ' . $e->getMessage());
        }
    }

    public function consultations(Request $request)
    {
        $query = Consultation::with(['assignedUser', 'waypoint']);
        $this->filterByDate($query, $request, 'created_at');
        $consultations = $query->get();

        return view('admin.consultations.index', compact('consultations'));
    }

    public function exportConsultations(Request $request)
    {
        $query = Consultation::with(['assignedUser', 'waypoint']);
        $this->filterByDate($query, $request, 'created_at');
        $consultations = $query->get();

        $filter = $request->get('filter', 'all');
        $filename = 'consultations_' . $filter . '_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($consultations) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Date Created',
                'Time Created',
                'Staff Member',
                'Location',
                'Ticket #',
                'Purpose of Visit',
                'Purpose Details',
                'Status',
                'Started At',
                'Completed At',
            ]);

            foreach ($consultations as $c) {
                $staff = ($c->assignedUser ? ($c->assignedUser->title ? $c->assignedUser->title . ' ' : '') . $c->assignedUser->name : 'N/A');
                $role = optional($c->assignedUser)->role;
                $offset = $role === 'principal' ? 100 : ($role === 'guidance_counselor' ? 200 : 0);
                $displayTicket = str_pad((string)((int)$c->ticket_number + $offset), 3, '0', STR_PAD_LEFT);

                fputcsv($handle, [
                    $c->id,
                    $c->created_at ? $c->created_at->format('Y-m-d') : '',
                    $c->created_at ? $c->created_at->format('h:i A') : '',
                    $staff,
                    optional($c->waypoint)->name ?? 'Walk-in/Unknown',
                    $displayTicket,
                    $c->purpose_code,
                    $c->purpose_other_text ?? '',
                    ucfirst(str_replace('_', ' ', $c->status)),
                    $c->started_at ? Carbon::parse($c->started_at)->format('Y-m-d h:i A') : 'Not started',
                    $c->completed_at ? Carbon::parse($c->completed_at)->format('Y-m-d h:i A') : '---',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function startConsultation(Request $request, Consultation $consultation)
    {
        if ($consultation->status === 'completed') {
            return back()->with('error', 'Already completed.');
        }

        $consultation->status = 'in_progress';
        $consultation->started_at = $consultation->started_at ?: now();
        $consultation->save();

        $ticket = $this->getDisplayTicket($consultation);
        ActivityLog::record('update_consultation', "Admin User Updated a Consultation - Ticket #{$ticket}.");

        return back()->with('success', 'Consultation started.');
    }

    public function finishConsultation(Request $request, Consultation $consultation)
    {
        if ($consultation->status === 'completed') {
            return back()->with('success', 'Consultation already completed.');
        }

        $consultation->status = 'completed';
        $consultation->completed_at = now();
        $consultation->save();

        $ticket = $this->getDisplayTicket($consultation);
        ActivityLog::record('update_consultation', "Admin User Updated a Consultation - Ticket #{$ticket}.");

        return back()->with('success', 'Consultation completed.');
    }

    public function destroyConsultation(Consultation $consultation)
    {
        $ticket = $this->getDisplayTicket($consultation);
        $consultation->delete();

        ActivityLog::record('delete_consultation', "Admin User Deleted a Consultation - Ticket #{$ticket}.");

        return back()->with('success', 'Consultation removed successfully.');
    }

    public function feedbacks(Request $request)
    {
        $query = Feedback::query();
        $this->filterByDate($query, $request, 'created_at');
        $feedbacks = $query->get();

        return view('admin.feedbacks.index', compact('feedbacks'));
    }

    public function destroyFeedback(Feedback $feedback)
    {
        $id = $feedback->id;
        $feedback->delete();

        ActivityLog::record('delete_feedback', "Admin User Deleted a Feedback - ID #{$id}.");

        return back()->with('success', 'Feedback removed.');
    }

    public function updateFeedback(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'rating' => 'required|string',
            'suggestions' => 'nullable|string',
        ]);

        $feedback->update($validated);
        $id = $feedback->id;

        ActivityLog::record('update_feedback', "Admin User Updated a Feedback - ID #{$id}.");

        return back()->with('success', 'Feedback updated successfully.');
    }

    public function exportFeedbacks(Request $request)
    {
        $query = Feedback::query();
        $this->filterByDate($query, $request, 'created_at');
        $feedbacks = $query->get();

        $filter = $request->get('filter', 'all');
        $filename = 'feedbacks_' . $filter . '_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($feedbacks) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Rating',
                'Suggestions',
                'Date',
                'Time',
            ]);

            foreach ($feedbacks as $f) {
                fputcsv($handle, [
                    $f->id,
                    $f->rating,
                    $f->suggestions ?: 'No suggestions',
                    $f->created_at ? $f->created_at->format('Y-m-d') : '',
                    $f->created_at ? $f->created_at->format('h:i A') : '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /** --- KIOSK SETTINGS & SERVICE HOURS --- */
    public function settings()
    {
        $settings = KioskSetting::getAllSettings();
        return view('admin.settings.index', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'service_hours_start' => 'required|string',
            'service_hours_end' => 'required|string',
            'service_days' => 'required|string',
            'kiosk_title' => 'required|string|max:255',
            'kiosk_status' => 'required|in:online,maintenance,offline',
            'contact_email' => 'nullable|email',
        ]);

        foreach ($validated as $key => $val) {
            KioskSetting::set($key, $val);
        }

        ActivityLog::record('update_service_hours', "Admin User Updated a Service Hours");

        return back()->with('success', 'Kiosk settings and service hours updated successfully.');
    }

    /**
     * Export the clean 8-table database schema and data as a downloadable .sql file for documentation/backup.
     */
    public function exportDatabase()
    {
        $tables = [
            'users',
            'waypoints',
            'waypoint_user',
            'consultations',
            'appointment_requests',
            'feedbacks',
            'activity_logs',
            'kiosk_settings',
        ];

        $filename = 'mit_campus_kiosk_database_' . date('Y-m-d_His') . '.sql';

        return response()->streamDownload(function () use ($tables) {
            $out = fopen('php://output', 'w');
            fwrite($out, "-- ============================================================\n");
            fwrite($out, "-- MIT Campus Guide Kiosk & Staff Portal - Database Export\n");
            fwrite($out, "-- Generated at: " . now()->toDateTimeString() . "\n");
            fwrite($out, "-- Core System Tables: " . implode(', ', $tables) . "\n");
            fwrite($out, "-- ============================================================\n\n");

            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();

            foreach ($tables as $table) {
                if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                    continue;
                }

                $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
                $rows = \Illuminate\Support\Facades\DB::table($table)->get();

                fwrite($out, "-- ------------------------------------------------------------\n");
                fwrite($out, "-- Table: {$table} (" . count($rows) . " rows)\n");
                fwrite($out, "-- Columns: " . implode(', ', $columns) . "\n");
                fwrite($out, "-- ------------------------------------------------------------\n");

                foreach ($rows as $row) {
                    $rowArr = (array) $row;
                    $colNames = array_map(fn ($c) => "\"{$c}\"", array_keys($rowArr));
                    $vals = array_map(function ($v) use ($pdo) {
                        if ($v === null) {
                            return 'NULL';
                        }
                        if (is_bool($v)) {
                            return $v ? 'TRUE' : 'FALSE';
                        }
                        if (is_int($v) || is_float($v)) {
                            return (string) $v;
                        }
                        return $pdo->quote((string) $v);
                    }, array_values($rowArr));

                    fwrite(
                        $out,
                        "INSERT INTO \"{$table}\" (" . implode(', ', $colNames) . ") VALUES (" . implode(', ', $vals) . ");\n"
                    );
                }
                fwrite($out, "\n");
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'application/sql; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /** --- ACTIVITY LOGS --- */
    public function activityLogs(Request $request)
    {
        $query = ActivityLog::query();

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
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        $this->filterByDate($query, $request, 'created_at');
        $logs = $query->paginate(20)->withQueryString();

        return view('admin.activity_logs.index', compact('logs'));
    }

    public function exportActivityLogs(Request $request)
    {
        $query = ActivityLog::query();

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
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        $this->filterByDate($query, $request, 'created_at');
        $logs = $query->get();

        $filter = $request->get('filter', 'all');
        $filename = 'activity_logs_' . $filter . '_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Date',
                'Time',
                'Actor',
                'Role',
                'Action',
                'Description',
                'IP Address',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at ? $log->created_at->format('Y-m-d') : '',
                    $log->created_at ? $log->created_at->format('h:i A') : '',
                    $log->user_name,
                    ucfirst($log->user_role ?? 'System'),
                    $log->action,
                    $log->description,
                    $log->ip_address ?? 'N/A',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
