<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class MobileConsultationsController extends Controller
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

    private function toPayload(Consultation $c, ?string $role): array
    {
        return [
            'id' => $c->id,
            'assigned_user_id' => $c->assigned_user_id,
            'assigned_user_name' => $c->assignedUser?->name,
            'assigned_user_status' => $c->assignedUser?->availability_status,
            'assigned_user_time_availability' => $c->assignedUser?->time_availability,
            'waypoint_id' => $c->waypoint_id,
            'waypoint_name' => $c->waypoint?->name,
            'ticket_number' => $c->ticket_number,
            'display_ticket_number' => $this->displayTicketNumber((int) $c->ticket_number, $role),
            'purpose_code' => $c->purpose_code,
            'purpose_other_text' => $c->purpose_other_text,
            'status' => $c->status,
            'created_at' => $c->created_at,
            'started_at' => $c->started_at,
            'completed_at' => $c->completed_at,
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $items = Consultation::query()
            ->with(['waypoint:id,name', 'assignedUser:id,name,role,availability_status,time_availability'])
            ->where('assigned_user_id', $user->id)
            ->orderBy('status')
            ->orderBy('created_at')
            ->get();

        return response()->json($items->map(fn ($c) => $this->toPayload($c, $user->role)));
    }

    public function start(Request $request, Consultation $consultation)
    {
        $user = $request->user();
        if ((int) $consultation->assigned_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($consultation->status === 'completed') {
            return response()->json(['message' => 'Already completed.'], 409);
        }

        $consultation->status = 'in_progress';
        $consultation->started_at = $consultation->started_at ?: now();
        $consultation->save();

        $displayTicket = $this->displayTicketNumber((int) $consultation->ticket_number, $user->role);
        ActivityLog::record('updated_consultation', "Staff Updated a Consultation - Ticket #{$displayTicket}.", ['id' => $consultation->id], $user);

        $consultation->loadMissing(['waypoint:id,name']);
        return response()->json($this->toPayload($consultation, $user->role));
    }

    public function finish(Request $request, Consultation $consultation)
    {
        $user = $request->user();
        if ((int) $consultation->assigned_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($consultation->status === 'completed') {
            return response()->json($consultation);
        }

        $consultation->status = 'completed';
        $consultation->completed_at = now();
        $consultation->save();

        $displayTicket = $this->displayTicketNumber((int) $consultation->ticket_number, $user->role);
        ActivityLog::record('updated_consultation', "Staff Updated a Consultation - Ticket #{$displayTicket}.", ['id' => $consultation->id], $user);

        $consultation->loadMissing(['waypoint:id,name']);
        return response()->json($this->toPayload($consultation, $user->role));
    }
}
