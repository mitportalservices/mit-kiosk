@extends('layouts.app')

@section('content')
@include('admin.partials.navbar')

<main class="pl-64 pt-16 min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
    <div class="p-8 max-w-5xl mx-auto">
        <header class="mb-8">
            <h1 class="text-4xl font-extrabold font-manrope tracking-tight text-slate-800 dark:text-slate-100">Kiosk Settings</h1>
            <p class="text-slate-500 dark:text-slate-400">Configure operating service hours, kiosk branding, and availability status.</p>
        </header>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-emerald-900/20 text-green-600 dark:text-emerald-400 font-bold rounded-2xl border border-green-100 dark:border-emerald-800/30 flex items-center gap-3 transition-all">
                <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-rose-900/20 text-red-600 dark:text-rose-400 font-bold rounded-2xl border border-red-100 dark:border-rose-800/30 flex items-center gap-3 transition-all">
                <span class="material-symbols-outlined text-rose-500">report</span>
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Service Hours Section -->
            <div class="bg-white dark:bg-slate-900 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 transition-colors">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-2xl">schedule</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold font-manrope text-slate-800 dark:text-slate-100">Service Hours Configuration</h2>
                        <p class="text-xs text-slate-400 font-medium">Define active hours for campus consultations and appointments.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Service Opening Time</label>
                        <input type="time" name="service_hours_start" value="{{ old('service_hours_start', $settings['service_hours_start'] ?? '08:00') }}" required
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Service Closing Time</label>
                        <input type="time" name="service_hours_end" value="{{ old('service_hours_end', $settings['service_hours_end'] ?? '17:00') }}" required
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Operating Service Days</label>
                        <input type="text" name="service_days" value="{{ old('service_days', $settings['service_days'] ?? 'Monday - Friday') }}" required
                            placeholder="e.g. Monday - Friday, 8:00 AM - 5:00 PM"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>

            <!-- Kiosk Branding & System Control -->
            <div class="bg-white dark:bg-slate-900 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 transition-colors">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <span class="material-symbols-outlined text-2xl">display_settings</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold font-manrope text-slate-800 dark:text-slate-100">Kiosk System & Display</h2>
                        <p class="text-xs text-slate-400 font-medium">General kiosk parameters and system operating status.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Kiosk Title Display</label>
                        <input type="text" name="kiosk_title" value="{{ old('kiosk_title', $settings['kiosk_title'] ?? 'Campus Guide - MIT') }}" required
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Operating Status</label>
                        <select name="kiosk_status" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="online" {{ ($settings['kiosk_status'] ?? '') === 'online' ? 'selected' : '' }}>🟢 Online (Normal Operation)</option>
                            <option value="maintenance" {{ ($settings['kiosk_status'] ?? '') === 'maintenance' ? 'selected' : '' }}>🟡 Maintenance Mode</option>
                            <option value="offline" {{ ($settings['kiosk_status'] ?? '') === 'offline' ? 'selected' : '' }}>🔴 Offline</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Support Contact Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? 'support@mit-maps.com') }}"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <button type="submit"
                    class="px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-sm uppercase tracking-wider rounded-2xl shadow-xl shadow-blue-600/25 flex items-center gap-2 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-xl">save</span>
                    Update Service Hours & Settings
                </button>
            </div>
        </form>

        <!-- Email & SMTP Diagnostics Section -->
        <div class="mt-8 bg-white dark:bg-slate-900 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 transition-colors">
            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <span class="material-symbols-outlined text-2xl">mark_email_read</span>
                </div>
                <div>
                    <h2 class="text-xl font-extrabold font-manrope text-slate-800 dark:text-slate-100">Email & SMTP Diagnostics</h2>
                    <p class="text-xs text-slate-400 font-medium">Verify your email credentials and test sending real emails to appointments and OTP recipients.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Mail Driver</span>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-1 uppercase">{{ config('mail.default') }}</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">SMTP Host / Port</span>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-1">{{ config('mail.mailers.smtp.host') }}:{{ config('mail.mailers.smtp.port') }} ({{ config('mail.mailers.smtp.encryption') ?: 'none' }})</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">From Address</span>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-1 truncate">{{ config('mail.from.address') }}</p>
                </div>
            </div>

            <form action="{{ route('admin.settings.test_email') }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                @csrf
                <div class="flex-1">
                    <input type="email" name="email" value="{{ auth()->user()->email ?? config('mail.from.address') }}" required
                        placeholder="Recipient email address"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-lg">send</span>
                    Send Test Email
                </button>
            </form>
        </div>

        <!-- Database Export & Schema Section -->
        <div class="mt-8 bg-white dark:bg-slate-900 p-8 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <span class="material-symbols-outlined text-2xl">database</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold font-manrope text-slate-800 dark:text-slate-100">Database Backup & Documentation Export</h2>
                        <p class="text-xs text-slate-400 font-medium">Download the clean SQL dump of all 8 active system tables (Users, Waypoints, Waypoint Staff, Consultations, Appointments, Feedbacks, Activity Logs, Kiosk Settings).</p>
                    </div>
                </div>
                <a href="{{ route('admin.settings.export_database') }}"
                    class="px-6 py-3 bg-slate-900 dark:bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-2xl shadow-lg flex items-center justify-center gap-2 transition-all active:scale-95 shrink-0">
                    <span class="material-symbols-outlined text-lg">download</span>
                    Export Database (.sql)
                </a>
            </div>
        </div>
    </div>
</main>
@endsection
