@extends('layouts.app')

@section('content')
<main class="min-h-screen bg-[#F0F4FA] dark:bg-slate-950 selection:bg-primary/10 transition-colors duration-300">
    <div class="max-w-[1200px] mx-auto p-12">
        <!-- Header -->
        <header class="mb-12">
            <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-2 mb-6 text-sm font-black text-primary dark:text-blue-400 uppercase tracking-[0.2em] hover:gap-3 transition-all">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                Back to Accounts
            </a>
            <h1 class="text-5xl font-black tracking-tight text-slate-800 dark:text-slate-100">Edit Account</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2 text-lg">Modify profile details, permissions, and availability for <span class="text-primary dark:text-blue-400 font-bold">{{ $user->name }}</span>.</p>
        </header>

        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            @csrf
            
            <!-- Left: Form Controls -->
            <div class="lg:col-span-7 space-y-8">
                <section class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm space-y-8 transition-colors">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                            <span class="material-symbols-outlined">manage_accounts</span>
                        </span>
                        <h3 class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight">Identity & Security</h3>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-1">
                                <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">Honorific</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 dark:text-slate-600">badge</span>
                                    <select name="title" 
                                        class="w-full pl-14 pr-6 py-5 bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent rounded-[1.25rem] text-slate-800 dark:text-slate-100 focus:border-primary/20 dark:focus:border-blue-500/30 focus:bg-white dark:focus:bg-slate-800 transition-all font-bold appearance-none">
                                        <option value="" {{ $user->title == '' ? 'selected' : '' }}>None</option>
                                        <option value="Mr." {{ $user->title == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                        <option value="Ms." {{ $user->title == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                                        <option value="Mx." {{ $user->title == 'Mx.' ? 'selected' : '' }}>Mx.</option>
                                        <option value="Dr." {{ $user->title == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                                        <option value="Prof." {{ $user->title == 'Prof.' ? 'selected' : '' }}>Prof.</option>
                                    </select>
                                </div>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">Full Name</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 dark:text-slate-600">person</span>
                                    <input type="text" name="name" value="{{ $user->name }}" required 
                                        class="w-full pl-14 pr-6 py-5 bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent rounded-[1.25rem] text-slate-800 dark:text-slate-100 focus:border-primary/20 dark:focus:border-blue-500/30 focus:bg-white dark:focus:bg-slate-800 transition-all font-bold">
                                </div>
                                @error('name') <p class="mt-2 text-xs text-red-500 font-bold ml-2">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">Email Address</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 dark:text-slate-600">alternate_email</span>
                                <input type="email" name="email" value="{{ $user->email }}" required 
                                    class="w-full pl-14 pr-6 py-5 bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent rounded-[1.25rem] text-slate-800 dark:text-slate-100 focus:border-primary/20 dark:focus:border-blue-500/30 focus:bg-white dark:focus:bg-slate-800 transition-all font-bold">
                            </div>
                            @error('email') <p class="mt-2 text-xs text-red-500 font-bold ml-2">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">New Password (Optional)</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 dark:text-slate-600">lock_reset</span>
                                <input type="password" name="password" 
                                    class="w-full pl-14 pr-6 py-5 bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent rounded-[1.25rem] text-slate-800 dark:text-slate-100 focus:border-primary/20 dark:focus:border-blue-500/30 focus:bg-white dark:focus:bg-slate-800 transition-all font-bold placeholder:text-slate-300 dark:placeholder:text-slate-600" placeholder="Leave blank to keep current">
                            </div>
                            @error('password') <p class="mt-2 text-xs text-red-500 font-bold ml-2">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3 text-primary dark:text-blue-400">Availability Pulse</label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="availability_status" value="available" class="hidden peer" {{ $user->availability_status == 'available' ? 'checked' : '' }}>
                                    <div class="py-3 px-4 rounded-xl border-2 border-slate-50 dark:border-slate-800 flex items-center justify-center gap-2 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 transition-all text-slate-400 dark:text-slate-500 peer-checked:text-green-600 dark:peer-checked:text-green-400">
                                        <span class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]"></span>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Available</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="availability_status" value="away" class="hidden peer" {{ $user->availability_status == 'away' ? 'checked' : '' }}>
                                    <div class="py-3 px-4 rounded-xl border-2 border-slate-50 dark:border-slate-800 flex items-center justify-center gap-2 peer-checked:border-orange-500 peer-checked:bg-orange-50 dark:peer-checked:bg-orange-900/20 transition-all text-slate-400 dark:text-slate-500 peer-checked:text-orange-600 dark:peer-checked:text-orange-400">
                                        <span class="w-2 h-2 rounded-full bg-orange-500 shadow-[0_0_8px_rgba(249,115,22,0.5)]"></span>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Away</span>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="availability_status" value="busy" class="hidden peer" {{ $user->availability_status == 'busy' ? 'checked' : '' }}>
                                    <div class="py-3 px-4 rounded-xl border-2 border-slate-50 dark:border-slate-800 flex items-center justify-center gap-2 peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-rose-900/20 transition-all text-slate-400 dark:text-slate-500 peer-checked:text-red-600 dark:peer-checked:text-rose-400">
                                        <span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]"></span>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Busy</span>
                                    </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">Time Availability</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 dark:text-slate-600">schedule</span>
                                <input type="text" name="time_availability" value="{{ old('time_availability', $user->time_availability ?? '8:00 AM - 5:00 PM') }}"
                                    class="w-full pl-14 pr-6 py-5 bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent rounded-[1.25rem] text-slate-800 dark:text-slate-100 focus:border-primary/20 dark:focus:border-blue-500/30 focus:bg-white dark:focus:bg-slate-800 transition-all font-bold placeholder:text-slate-300 dark:placeholder:text-slate-600" placeholder="e.g. 8:00 AM - 5:00 PM">
                            </div>
                            <p class="mt-3 text-[10px] text-slate-400 dark:text-slate-500 font-bold flex items-center gap-1 uppercase tracking-widest">
                                <span class="material-symbols-outlined text-[14px]">info</span>
                                Displayed on appointment & consultation screens below status badge
                            </p>
                        </div>
                    </div>
                </section>

                <button type="submit" class="w-full py-6 bg-slate-800 dark:bg-blue-600 text-white rounded-3xl font-black uppercase tracking-[0.2em] text-sm shadow-2xl shadow-slate-200 dark:shadow-blue-900/20 hover:bg-slate-900 dark:hover:bg-blue-500 hover:-translate-y-1 active:translate-y-0 transition-all flex items-center justify-center gap-3 group">
                    <span>Apply Changes</span>
                    <span class="material-symbols-outlined text-[20px] transition-transform group-hover:rotate-12">save</span>
                </button>
            </div>

            <!-- Right: Role Selection -->
            <div class="lg:col-span-5">
                <section class="bg-white dark:bg-slate-900 p-10 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm h-full transition-colors">
                    <div class="flex items-center gap-3 mb-8">
                        <span class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                            <span class="material-symbols-outlined">shield_person</span>
                        </span>
                        <h3 class="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight">Permissions</h3>
                    </div>

                    <div class="space-y-4">
                        <label class="group relative flex items-center gap-4 p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent hover:border-primary/20 dark:hover:border-blue-500/30 cursor-pointer transition-all has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20 has-[:checked]:border-primary dark:has-[:checked]:border-blue-500">
                            <input type="radio" name="role" value="faculty" class="hidden" {{ $user->role == 'faculty' ? 'checked' : '' }}>
                            <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center border border-slate-200 dark:border-slate-700 group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 group-has-[:checked]:text-primary dark:group-has-[:checked]:text-blue-400 text-3xl">school</span>
                            </div>
                            <div>
                                <span class="block text-lg font-black text-slate-800 dark:text-slate-100 tracking-tight leading-none mb-1">Faculty</span>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">General Staff Member</span>
                            </div>
                            <div class="ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-primary dark:text-blue-400 font-black">check_circle</span>
                            </div>
                        </label>

                        <label class="group relative flex items-center gap-4 p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent hover:border-primary/20 dark:hover:border-blue-500/30 cursor-pointer transition-all has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20 has-[:checked]:border-primary dark:has-[:checked]:border-blue-500">
                            <input type="radio" name="role" value="admin" class="hidden" {{ $user->role == 'admin' ? 'checked' : '' }}>
                            <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center border border-slate-200 dark:border-slate-700 group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 group-has-[:checked]:text-primary dark:group-has-[:checked]:text-blue-400 text-3xl">admin_panel_settings</span>
                            </div>
                            <div>
                                <span class="block text-lg font-black text-slate-800 dark:text-slate-100 tracking-tight leading-none mb-1">System Admin</span>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Full System Access</span>
                            </div>
                            <div class="ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-primary dark:text-blue-400 font-black">check_circle</span>
                            </div>
                        </label>

                        <label class="group relative flex items-center gap-4 p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent hover:border-primary/20 dark:hover:border-blue-500/30 cursor-pointer transition-all has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20 has-[:checked]:border-primary dark:has-[:checked]:border-blue-500">
                            <input type="radio" name="role" value="principal" class="hidden" {{ $user->role == 'principal' ? 'checked' : '' }}>
                            <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center border border-slate-200 dark:border-slate-700 group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 group-has-[:checked]:text-primary dark:group-has-[:checked]:text-blue-400 text-3xl">badge</span>
                            </div>
                            <div>
                                <span class="block text-lg font-black text-slate-800 dark:text-slate-100 tracking-tight leading-none mb-1">Principal</span>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Executive Access</span>
                            </div>
                            <div class="ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-primary dark:text-blue-400 font-black">check_circle</span>
                            </div>
                        </label>

                        <label class="group relative flex items-center gap-4 p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border-2 border-transparent hover:border-primary/20 dark:hover:border-blue-500/30 cursor-pointer transition-all has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20 has-[:checked]:border-primary dark:has-[:checked]:border-blue-500">
                            <input type="radio" name="role" value="guidance_counselor" class="hidden" {{ $user->role == 'guidance_counselor' ? 'checked' : '' }}>
                            <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center border border-slate-200 dark:border-slate-700 group-hover:scale-110 transition-transform">
                                <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 group-has-[:checked]:text-primary dark:group-has-[:checked]:text-blue-400 text-3xl">psychology</span>
                            </div>
                            <div>
                                <span class="block text-lg font-black text-slate-800 dark:text-slate-100 tracking-tight leading-none mb-1">Guidance</span>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Counseling Dept.</span>
                            </div>
                            <div class="ml-auto opacity-0 group-has-[:checked]:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-primary dark:text-blue-400 font-black">check_circle</span>
                            </div>
                        </label>
                    </div>

                    <div class="mt-12 p-8 bg-blue-50 dark:bg-blue-900/20 rounded-[2rem] border border-blue-100 dark:border-blue-800/30 transition-colors">
                        <div class="flex items-center gap-3 mb-2 text-blue-600 dark:text-blue-400">
                            <span class="material-symbols-outlined">security</span>
                            <span class="font-black uppercase tracking-widest text-[10px]">Access History</span>
                        </div>
                        <p class="text-xs text-blue-400 dark:text-blue-500 font-medium leading-relaxed">
                            Last active: <span class="text-blue-700 dark:text-blue-300 font-bold">{{ $user->last_login_at ?? 'Never logged in' }}</span>
                        </p>
                    </div>
                </section>
            </div>
        </form>
    </div>
</main>
@endsection
