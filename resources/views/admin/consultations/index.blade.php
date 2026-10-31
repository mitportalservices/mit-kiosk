@extends('layouts.app')

@section('content')
@include('admin.partials.navbar')

<main class="pl-64 pt-16 min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
    <div class="p-8 max-w-[95rem] mx-auto">
        <header class="mb-8 flex flex-wrap justify-between items-end gap-4">
            <div>
                <h1 class="text-4xl font-extrabold font-manrope tracking-tight text-slate-800 dark:text-slate-100">Consultations</h1>
                <p class="text-slate-500 dark:text-slate-400">All kiosk-created consultations and their queue status.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.consultations.export', request()->query()) }}" 
                    class="px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/20 flex items-center gap-2 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-lg">table_view</span>
                    Export CSV / Excel
                </a>
                <div class="px-5 py-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col items-end">
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Total Consultations</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-slate-100">{{ $consultations->count() }}</span>
                </div>
            </div>
        </header>

        @include('admin.partials.filter-bar', [
            'routeName' => 'admin.consultations',
            'exportRoute' => 'admin.consultations.export',
            'totalCount' => $consultations->count(),
            'label' => 'Consultations'
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
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Staff Member</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Location</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Ticket #</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Purpose of Visit</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Status</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Started At</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Completed At</th>
                            <th class="text-left px-6 py-4 font-black uppercase tracking-widest text-[10px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($consultations as $c)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $c->created_at->format('M d, Y') }}</div>
                                    <div class="text-[10px] opacity-60 uppercase">{{ $c->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4 text-slate-800 dark:text-slate-200">
                                    <div class="font-bold">{{ $c->assignedUser->name ?? 'Unassigned' }}</div>
                                    @if($c->assignedUser)
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $c->assignedUser->availability_status === 'away' ? 'bg-orange-500' : ($c->assignedUser->availability_status === 'busy' ? 'bg-rose-500' : 'bg-green-500') }}"></span>
                                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ ucfirst($c->assignedUser->availability_status ?? 'available') }}</span>
                                        </div>
                                        <div class="mt-0.5 text-[10px] font-bold text-primary flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[12px]">schedule</span>
                                            {{ $c->assignedUser->time_availability ?: '8:00 AM - 5:00 PM' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">
                                    {{ optional($c->waypoint)->name ?? 'Walk-in/Unknown' }}
                                </td>
                                @php
                                    $role = optional($c->assignedUser)->role;
                                    $offset = $role === 'principal' ? 100 : ($role === 'guidance_counselor' ? 200 : 0);
                                    $displayTicket = str_pad((string)((int)$c->ticket_number + $offset), 3, '0', STR_PAD_LEFT);
                                @endphp
                                <td class="px-6 py-4 font-black text-blue-600 dark:text-blue-400 text-2xl tracking-tighter">{{ $displayTicket }}</td>
                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300">
                                    <div class="font-bold">{{ $c->purpose_code }}</div>
                                    @if(!empty($c->purpose_other_text))
                                        <div class="text-xs text-slate-400 dark:text-slate-500 italic">{{ $c->purpose_other_text }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClasses = [
                                            'waiting' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-100 dark:border-amber-800/30',
                                            'in_progress' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-100 dark:border-blue-800/30',
                                            'finished' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800/30',
                                        ];
                                        $cls = $statusClasses[$c->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700';
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border {{ $cls }}">
                                        {{ str_replace('_', ' ', $c->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    @if($c->started_at)
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ \Carbon\Carbon::parse($c->started_at)->format('h:i A') }}</div>
                                        <div class="text-[10px] opacity-60 uppercase">{{ \Carbon\Carbon::parse($c->started_at)->format('M d') }}</div>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-600 italic">Not started</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    @if($c->completed_at)
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ \Carbon\Carbon::parse($c->completed_at)->format('h:i A') }}</div>
                                        <div class="text-[10px] opacity-60 uppercase">{{ \Carbon\Carbon::parse($c->completed_at)->format('M d') }}</div>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-600 italic">---</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        @if($c->status === 'waiting')
                                            <form method="POST" action="{{ route('admin.consultations.start', $c) }}">
                                                @csrf
                                                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs shadow-sm shadow-blue-900/20 transition-all">Start</button>
                                            </form>
                                        @elseif($c->status === 'in_progress')
                                            <form method="POST" action="{{ route('admin.consultations.finish', $c) }}">
                                                @csrf
                                                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-sm shadow-emerald-900/20 transition-all">Finish</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.consultations.destroy', $c) }}" onsubmit="return confirm('Delete this consultation ticket?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-all" title="Delete Consultation">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400 dark:text-slate-600 font-bold">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-4xl opacity-20">group_off</span>
                                        <span>No consultations yet.</span>
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
