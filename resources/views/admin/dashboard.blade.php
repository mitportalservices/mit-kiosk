@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
<!-- Admin Navbar & Sidebar Partial -->
@include('admin.partials.navbar')

<!-- Main Content Canvas -->
<main class="pl-64 pt-16 min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
<div class="p-8 max-w-7xl mx-auto">
    <!-- Quick Stats Bento -->
    <header class="mb-10 flex justify-between items-end">
        <div>
            <h1 class="text-4xl font-extrabold font-manrope tracking-tight text-slate-800 dark:text-slate-100 mb-2">Campus Overview</h1>
            <p class="text-slate-500 dark:text-slate-400 max-w-lg">Real-time status of your digital campus guide and educational synchronization.</p>
        </div>
        <div class="flex gap-4">
            <div class="px-6 py-4 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col transition-all">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Faculty Accounts</span>
                <span class="text-2xl font-black text-slate-800 dark:text-slate-100">{{ (int) ($staffCounts['faculty'] ?? 0) }}</span>
            </div>
            <div class="px-6 py-4 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col transition-all">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Principal Accounts</span>
                <span class="text-2xl font-black text-slate-800 dark:text-slate-100">{{ (int) ($staffCounts['principal'] ?? 0) }}</span>
            </div>
            <div class="px-6 py-4 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col transition-all">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Guidance Accounts</span>
                <span class="text-2xl font-black text-slate-800 dark:text-slate-100">{{ (int) ($staffCounts['guidance_counselor'] ?? 0) }}</span>
            </div>
            <div class="px-6 py-4 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col transition-all">
                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Active Waypoints</span>
                <span class="text-2xl font-black text-slate-800 dark:text-slate-100">{{ $waypoints->count() }}</span>
            </div>
        </div>
    </header>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-emerald-900/20 text-green-600 dark:text-emerald-400 font-bold rounded-xl border border-green-100 dark:border-emerald-800/30 flex items-center gap-2 transition-all">
            <span class="material-symbols-outlined text-emerald-500">check_circle</span>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-rose-900/20 text-red-600 dark:text-rose-400 font-bold rounded-xl border border-red-100 dark:border-rose-800/30 flex items-center gap-2 transition-all">
            <span class="material-symbols-outlined text-rose-500">report</span>
            {{ session('error') }}
        </div>
    @endif

    <!-- Mobile App Download Card (Android APK + iOS/Web Portal) -->
    <div class="mb-10 p-6 bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center text-primary shrink-0">
                <span class="material-symbols-outlined text-3xl">smartphone</span>
            </div>
            <div>
                <h2 class="text-lg font-black font-manrope text-slate-800 dark:text-slate-100">MIT Staff &amp; Admin Mobile App</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                    Downloadable link for the mobile app for faculty, guidance counselors, and administrators to install the Android APK directly onto their phones.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('download.apk') }}"
                class="px-5 py-3 bg-gradient-to-r from-primary to-blue-600 text-white rounded-2xl font-bold text-xs shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-base">download</span>
                Download Android APK
            </a>
            <a href="/mobile" target="_blank"
                class="px-5 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-2xl font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-base">open_in_new</span>
                Open iOS / Web Portal
            </a>
        </div>
    </div>

    <!-- Recent Activity / Waypoint Preview -->
    <section>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-black font-manrope text-slate-800 dark:text-slate-100">Recent Waypoints</h2>
            <a href="{{ route('admin.waypoints') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1 hover:underline transition-all">
                Full Management
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            @forelse($waypoints->take(3) as $waypoint)
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-md dark:hover:bg-slate-800/50 transition-all group">
                <div class="flex justify-between items-start mb-6">
                    <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl">location_on</span>
                    </div>
                    <span class="text-[10px] uppercase tracking-widest font-black text-slate-300 dark:text-slate-600">{{ $waypoint->location_code }}</span>
                </div>
                <h3 class="text-xl font-black font-manrope text-slate-800 dark:text-slate-100 mb-1 leading-tight">{{ $waypoint->name }}</h3>
                <p class="text-sm text-slate-400 dark:text-slate-500 font-medium mb-4">Floor {{ $waypoint->floor ?? '1' }} • {{ $waypoint->location_code }}</p>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Active & Pin-Sync'd</span>
                </div>
            </div>
            @empty
                <div class="col-span-3 py-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800">
                    <p class="text-slate-400 dark:text-slate-600 font-bold italic">Start by adding your first waypoint.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Feedback Section -->
    <section class="mt-12 mb-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-black font-manrope text-slate-800 dark:text-slate-100">Kiosk Feedback</h2>
                <p class="text-sm text-slate-400 dark:text-slate-500 font-medium mt-0.5">Ratings and suggestions from kiosk transactions</p>
            </div>
            <a href="{{ route('admin.feedbacks') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1 hover:underline transition-all">
                View All
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        @if($feedbacks->isEmpty())
            <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 transition-all">
                <div class="flex flex-col items-center gap-3">
                    <span class="text-5xl">💬</span>
                    <p class="text-slate-500 dark:text-slate-400 font-bold">No feedback collected yet.</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Feedback from visitors will appear here after completed transactions.</p>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 w-8">#</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Rating</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Suggestions</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 whitespace-nowrap">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($feedbacks as $index => $feedback)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 text-xs font-bold text-slate-400 dark:text-slate-500">{{ $index + 1 }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $ratingConfig = [
                                            'Very Satisfied'    => ['emoji' => '😄', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20', 'text' => 'text-emerald-700 dark:text-emerald-300'],
                                            'Satisfied'         => ['emoji' => '🙂', 'bg' => 'bg-green-50 dark:bg-green-900/20',   'text' => 'text-green-700 dark:text-green-300'],
                                            'Neutral'           => ['emoji' => '😐', 'bg' => 'bg-slate-100 dark:bg-slate-800',      'text' => 'text-slate-600 dark:text-slate-300'],
                                            'Dissatisfied'      => ['emoji' => '🙁', 'bg' => 'bg-amber-50 dark:bg-amber-900/20',   'text' => 'text-amber-700 dark:text-amber-300'],
                                            'Very Dissatisfied' => ['emoji' => '😞', 'bg' => 'bg-rose-50 dark:bg-rose-900/20',     'text' => 'text-rose-700 dark:text-rose-300'],
                                        ];
                                        $cfg = $ratingConfig[$feedback->rating] ?? ['emoji' => '❓', 'bg' => 'bg-slate-100', 'text' => 'text-slate-500'];
                                    @endphp
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl {{ $cfg['bg'] }} {{ $cfg['text'] }} text-xs font-black whitespace-nowrap">
                                        <span>{{ $cfg['emoji'] }}</span>
                                        <span>{{ $feedback->rating }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300 max-w-xs">
                                    @if($feedback->suggestions)
                                        <span class="font-medium">{{ $feedback->suggestions }}</span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600 italic text-xs font-bold">No suggestions</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-slate-400 dark:text-slate-500 whitespace-nowrap">
                                    {{ $feedback->created_at->format('M d, Y') }}<br>
                                    <span class="text-slate-300 dark:text-slate-600 font-medium">{{ $feedback->created_at->format('h:i A') }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>

    <!-- Recent Activity Logs Section -->
    <section class="mt-12 mb-12">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-black font-manrope text-slate-800 dark:text-slate-100">Recent Activity Logs</h2>
                <p class="text-sm text-slate-400 dark:text-slate-500 font-medium mt-0.5">Real-time audit records of kiosk and account operations</p>
            </div>
            <a href="{{ route('admin.activity_logs') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1 hover:underline transition-all">
                View All Logs
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        @if($recentLogs->isEmpty())
            <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 transition-all">
                <div class="flex flex-col items-center gap-3">
                    <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-700">history</span>
                    <p class="text-slate-500 dark:text-slate-400 font-bold">No activity recorded yet.</p>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Date & Time</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Actor</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Action</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                            @foreach($recentLogs as $log)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ $log->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-black text-slate-800 dark:text-slate-200 text-xs">{{ $log->user_name }}</span>
                                    <span class="ml-2 text-[9px] font-black uppercase px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">{{ $log->user_role }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-bold text-primary">{{ str_replace('_', ' ', $log->action) }}</span>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-700 dark:text-slate-300">
                                    {{ $log->description }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>

</div>
</main>
</div>
@endsection

