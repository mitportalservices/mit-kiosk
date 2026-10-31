@extends('layouts.app')

@section('content')
@include('admin.partials.navbar')

<main class="pl-64 pt-16 min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
    <div class="p-8 max-w-[95rem] mx-auto">
        <header class="mb-8 flex flex-wrap justify-between items-end gap-4">
            <div>
                <h1 class="text-4xl font-extrabold font-manrope tracking-tight text-slate-800 dark:text-slate-100">Appointment Requests</h1>
                <p class="text-slate-500 dark:text-slate-400">All kiosk-created appointment requests.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.appointments.export', request()->query()) }}" 
                    class="px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/20 flex items-center gap-2 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-lg">table_view</span>
                    Export CSV / Excel
                </a>
                <div class="px-5 py-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col items-end">
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Total Appointments</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-slate-100">{{ $appointments->count() }}</span>
                </div>
            </div>
        </header>

        @include('admin.partials.filter-bar', [
            'routeName' => 'admin.appointments',
            'exportRoute' => 'admin.appointments.export',
            'totalCount' => $appointments->count(),
            'label' => 'Appointments'
        ])

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-emerald-900/20 text-green-600 dark:text-emerald-400 font-bold rounded-xl border border-green-100 dark:border-emerald-800/30 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-rose-900/20 text-red-600 dark:text-rose-400 font-bold rounded-xl border border-red-100 dark:border-rose-800/30 flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-500">report</span>
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden transition-colors duration-300">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Created At</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Staff Target</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Requester Details</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Contact Email</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Purpose of Visit</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Status</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Scheduled For</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($appointments as $a)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $a->created_at->format('M d, Y') }}</div>
                                    <div class="text-[10px] opacity-60 uppercase">{{ $a->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 text-slate-800 dark:text-slate-200">
                                    <div class="font-bold">{{ $a->targetUser->name ?? 'Unassigned' }}</div>
                                    @if($a->targetUser)
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $a->targetUser->availability_status === 'away' ? 'bg-orange-500' : ($a->targetUser->availability_status === 'busy' ? 'bg-rose-500' : 'bg-green-500') }}"></span>
                                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ ucfirst($a->targetUser->availability_status ?? 'available') }}</span>
                                        </div>
                                        <div class="mt-0.5 text-[10px] font-bold text-primary flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[12px]">schedule</span>
                                            {{ $a->targetUser->time_availability ?: '8:00 AM - 5:00 PM' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300">
                                    <div class="font-bold">{{ $a->requester_honorific }} {{ $a->requester_name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 capitalize">{{ $a->requester_type }} • {{ $a->grade_level ?: 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400">{{ $a->requester_email }}</td>
                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300">
                                    <div class="font-bold">{{ $a->purpose_code }}</div>
                                    @if(!empty($a->purpose_other_text))
                                        <div class="text-xs text-slate-400 dark:text-slate-500 italic">{{ $a->purpose_other_text }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                                            'accepted' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800/30',
                                            'declined' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-100 dark:border-amber-800/30',
                                        ];
                                        $cls = $statusClasses[$a->status] ?? $statusClasses['pending'];
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $cls }}">
                                        {{ $a->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    @if($a->scheduled_at)
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ \Carbon\Carbon::parse($a->scheduled_at)->format('M d, Y') }}</div>
                                        <div class="text-[10px] opacity-60 uppercase">{{ \Carbon\Carbon::parse($a->scheduled_at)->format('h:i A') }}</div>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-600 italic">Unscheduled</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($a->status === 'pending')
                                        <div class="flex flex-col gap-2 min-w-[240px]">
                                            <form method="POST" action="{{ route('admin.appointments.accept', $a) }}" class="flex items-center gap-2">
                                                @csrf
                                                <div class="relative flex-1">
                                                    <input type="datetime-local" name="scheduled_at" required 
                                                        class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold text-[11px] focus:ring-2 focus:ring-emerald-500 outline-none transition-all" />
                                                </div>
                                                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-[11px] shadow-sm shadow-emerald-900/20 transition-all uppercase tracking-wider">Accept</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.appointments.decline', $a) }}">
                                                @csrf
                                                <button type="submit" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-500 hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-900/20 dark:hover:text-amber-400 font-black text-[10px] transition-all uppercase tracking-widest border border-slate-100 dark:border-slate-700">Decline Request</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.appointments.destroy', $a) }}" onsubmit="return confirm('Delete this appointment record?');" class="mt-1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full px-3 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400 font-bold text-[10px] transition-all uppercase tracking-widest border border-slate-100 dark:border-slate-700 flex items-center justify-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">delete</span>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-400 dark:text-slate-600 italic">Completed</span>
                                            <form method="POST" action="{{ route('admin.appointments.destroy', $a) }}" onsubmit="return confirm('Delete this appointment record?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400 font-bold text-[10px] transition-all uppercase tracking-widest border border-slate-100 dark:border-slate-700 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">delete</span>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400 dark:text-slate-600 font-bold">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-4xl opacity-20">event_busy</span>
                                        <span>No appointment requests yet.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
@endsection
