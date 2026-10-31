<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Waypoint;
use Illuminate\Http\Request;

class KioskQueueController extends Controller
{
    private function ticketOffsetForRole(?string $role): int
    {
        if ($role === 'principal') {
            return 100;
        }
        if ($role === 'guidance_counselor') {
            return 200;
        }
        return 0;
    }

    private function displayTicketNumber(int $ticketNumber, ?string $role): string
    {
        $n = $ticketNumber + $this->ticketOffsetForRole($role);
        return str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $waypoints = Waypoint::with([
            'assignedUsers:id,name,role,title,availability_status',
            'assignedUser:id,name,role,title,availability_status',
        ])->get();

        $items = Consultation::query()
            ->with([
                'assignedUser:id,name,role,title,availability_status',
                'waypoint:id,name,location_code,floor',
            ])
            ->whereIn('status', ['waiting', 'in_progress'])
            ->whereDate('created_at', now()->toDateString())
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'waiting' THEN 2 ELSE 3 END")
            ->orderBy('created_at')
            ->get();

        return response()->json($this->buildWaypointsQueueResponse($waypoints, $items));
    }

    public function show(Request $request, Waypoint $waypoint)
    {
        $waypoints = Waypoint::with([
            'assignedUsers:id,name,role,title,availability_status',
            'assignedUser:id,name,role,title,availability_status',
        ])->where('id', $waypoint->id)->get();

        if ($waypoints->isEmpty()) {
            $waypoints = collect([$waypoint->load(['assignedUsers', 'assignedUser'])]);
        }

        $items = Consultation::query()
            ->with([
                'assignedUser:id,name,role,title,availability_status',
                'waypoint:id,name,location_code,floor',
            ])
            ->where('waypoint_id', $waypoint->id)
            ->whereIn('status', ['waiting', 'in_progress'])
            ->whereDate('created_at', now()->toDateString())
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'waiting' THEN 2 ELSE 3 END")
            ->orderBy('created_at')
            ->get();

        return response()->json($this->buildWaypointsQueueResponse($waypoints, $items));
    }

    private function buildWaypointsQueueResponse($waypoints, $items)
    {
        $formatTicket = function ($row, ?string $role) {
            $raw = (int) $row->ticket_number;
            return [
                'id' => $row->id,
                'ticket_number' => str_pad((string) $raw, 3, '0', STR_PAD_LEFT),
                'display_ticket_number' => $this->displayTicketNumber($raw, $role),
                'status' => $row->status,
                'purpose' => $row->purpose_code,
                'created_at' => $row->created_at,
                'assigned_user' => $row->assignedUser ? [
                    'id' => $row->assignedUser->id,
                    'name' => $row->assignedUser->name,
                    'title' => $row->assignedUser->title,
                    'role' => $row->assignedUser->role,
                ] : null,
                'waypoint' => $row->waypoint ? [
                    'id' => $row->waypoint->id,
                    'name' => $row->waypoint->name,
                    'location_code' => $row->waypoint->location_code,
                ] : null,
            ];
        };

        $waypointCards = $waypoints->map(function ($wp) use ($items, $formatTicket) {
            // Find staff assigned to this waypoint
            $staff = $wp->assignedUser ?? $wp->assignedUsers->first();
            $role = $staff?->role ?? 'faculty';
            $staffId = $staff?->id;

            // Get consultations belonging to this waypoint or this staff
            $wpConsultations = $items->filter(function ($c) use ($wp, $staffId) {
                if ($c->waypoint_id && $c->waypoint_id == $wp->id) {
                    return true;
                }
                if ($staffId && $c->assigned_user_id == $staffId) {
                    return true;
                }
                return false;
            })->values();

            $current = $wpConsultations->first();
            $upNext = $wpConsultations->slice(1)->take(10)->values();

            // When a transaction is done and there are no active consultations, do not include this waypoint in the Now Serving queue
            if (!$current && $upNext->isEmpty()) {
                return null;
            }

            return [
                'waypoint' => [
                    'id' => $wp->id,
                    'name' => $wp->name,
                    'location_code' => $wp->location_code,
                    'floor' => $wp->floor,
                    'description' => $wp->description,
                ],
                'staff' => $staff ? [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'title' => $staff->title,
                    'role' => $staff->role,
                    'availability_status' => $staff->availability_status ?? 'available',
                    'time_availability' => $staff->time_availability,
                ] : null,
                'current' => $current ? $formatTicket($current, $role) : null,
                'up_next' => $upNext->map(fn ($r) => $formatTicket($r, $role))->values(),
                'up_next_total' => max(0, $wpConsultations->count() - 1),
            ];
        })->filter()->values();

        // Also build legacy groups for backward compatibility if any
        $byUser = $items->groupBy('assigned_user_id');
        $groups = $byUser->map(function ($list) use ($formatTicket) {
            $first = $list->first();
            $u = $first?->assignedUser;
            if (!$u) {
                return null;
            }
            $role = $u->role ?? 'faculty';
            $current = $list->first();
            $upNext = $list->slice(1)->take(10)->values();

            return [
                'user' => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'title' => $u->title,
                    'role' => $u->role,
                ],
                'current' => $current ? $formatTicket($current, $role) : null,
                'up_next' => $upNext->map(fn ($r) => $formatTicket($r, $role))->values(),
            ];
        })->filter()->values();

        $allUpcoming = $items->filter(fn ($c) => $c->status === 'waiting')
            ->take(10)
            ->map(function ($c) use ($formatTicket) {
                $role = $c->assignedUser?->role ?? 'faculty';
                return $formatTicket($c, $role);
            })->values();

        return [
            'waypoints' => $waypointCards,
            'groups' => $groups,
            'all_upcoming' => $allUpcoming,
            'updated_at' => now()->toISOString(),
        ];
    }
}
