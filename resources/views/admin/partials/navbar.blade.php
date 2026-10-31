<nav
    class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md shadow-sm flex justify-between items-center px-8 w-full h-16 fixed top-0 z-50 border-b border-slate-100 dark:border-slate-800 transition-colors duration-300">
    <div class="flex items-center gap-8">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center text-white">
                <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
            </div>
            <span class="text-xl font-black text-slate-800 dark:text-slate-100 font-manrope tracking-tighter">MIT Admin</span>
        </div>
        <div class="hidden md:flex items-center gap-1">
            <a class="px-3 py-2 rounded-xl font-bold text-xs transition-all {{ Route::is('admin.dashboard') ? 'bg-slate-50 dark:bg-slate-800 text-primary' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}"
                href="{{ route('admin.dashboard') }}">Overview</a>
            @if(auth()->user()->isAdmin())
                <a class="px-3 py-2 rounded-xl font-bold text-xs transition-all {{ Route::is('admin.waypoints*') ? 'bg-slate-50 dark:bg-slate-800 text-primary' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}"
                    href="{{ route('admin.waypoints') }}">Waypoints</a>
                <a class="px-3 py-2 rounded-xl font-bold text-xs transition-all {{ Route::is('admin.users*') ? 'bg-slate-50 dark:bg-slate-800 text-primary' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}"
                    href="{{ route('admin.users') }}">Accounts</a>
                <a class="px-3 py-2 rounded-xl font-bold text-xs transition-all {{ Route::is('admin.appointments*') ? 'bg-slate-50 dark:bg-slate-800 text-primary' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}"
                    href="{{ route('admin.appointments') }}">Appointments</a>
                <a class="px-3 py-2 rounded-xl font-bold text-xs transition-all {{ Route::is('admin.consultations*') ? 'bg-slate-50 dark:bg-slate-800 text-primary' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}"
                    href="{{ route('admin.consultations') }}">Consultations</a>
                <a class="px-3 py-2 rounded-xl font-bold text-xs transition-all {{ Route::is('admin.feedbacks*') ? 'bg-slate-50 dark:bg-slate-800 text-primary' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}"
                    href="{{ route('admin.feedbacks') }}">Feedback</a>
                <a class="px-3 py-2 rounded-xl font-bold text-xs transition-all {{ Route::is('admin.activity_logs*') ? 'bg-slate-50 dark:bg-slate-800 text-primary' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300' }}"
                    href="{{ route('admin.activity_logs') }}">Activity Logs</a>
            @endif
        </div>
    </div>
    <div class="flex items-center gap-6">
        <!-- Dark Mode Toggle -->
        <button id="theme-toggle"
            class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-primary hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-all group">
            <span
                class="material-symbols-outlined group-hover:rotate-12 transition-transform dark:hidden">dark_mode</span>
            <span
                class="material-symbols-outlined group-hover:-rotate-12 transition-transform hidden dark:block text-amber-400">light_mode</span>
        </button>

        <a href="{{ route('visitor.kiosk') }}" target="_blank"
            class="text-xs font-bold text-slate-400 hover:text-primary transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">open_in_new</span>
            Launch Kiosk
        </a>
        <div class="flex items-center gap-3 pl-6 border-l border-slate-100 dark:border-slate-800">
            <span
                class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-widest">{{ Auth::user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                    class="w-10 h-10 flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl transition-all">
                    <span class="material-symbols-outlined">logout</span>
                </button>
            </form>
        </div>
    </div>
</nav>

<!-- Sidebar (Admin) -->
<aside
    class="fixed left-0 top-0 h-full flex flex-col py-6 px-4 bg-white dark:bg-slate-900 border-r border-slate-100 dark:border-slate-800 w-64 pt-20 transition-colors duration-300">
    <div class="mb-4 px-4 py-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
        <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">System Version</p>
        <p class="text-xs font-bold text-slate-800 dark:text-slate-200">MIT Kiosk V4.0</p>
    </div>
    <nav class="flex-grow flex flex-col gap-1 overflow-y-auto">
        <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-bold text-sm transition-all {{ Route::is('admin.dashboard') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300' }}"
            href="{{ route('admin.dashboard') }}">
            <span class="material-symbols-outlined text-xl">dashboard</span>
            <span>Dashboard</span>
        </a>
        @if(auth()->user()->isAdmin())
            <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-bold text-sm transition-all {{ Route::is('admin.waypoints*') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300' }}"
                href="{{ route('admin.waypoints') }}">
                <span class="material-symbols-outlined text-xl">map</span>
                <span>Waypoints</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-bold text-sm transition-all {{ Route::is('admin.users*') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300' }}"
                href="{{ route('admin.users') }}">
                <span class="material-symbols-outlined text-xl">manage_accounts</span>
                <span>Accounts</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-bold text-sm transition-all {{ Route::is('admin.appointments*') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300' }}"
                href="{{ route('admin.appointments') }}">
                <span class="material-symbols-outlined text-xl">event</span>
                <span>Appointments</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-bold text-sm transition-all {{ Route::is('admin.consultations*') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300' }}"
                href="{{ route('admin.consultations') }}">
                <span class="material-symbols-outlined text-xl">forum</span>
                <span>Consultations</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-bold text-sm transition-all {{ Route::is('admin.feedbacks*') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300' }}"
                href="{{ route('admin.feedbacks') }}">
                <span class="material-symbols-outlined text-xl">rate_review</span>
                <span>Feedback</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-bold text-sm transition-all {{ Route::is('admin.activity_logs*') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300' }}"
                href="{{ route('admin.activity_logs') }}">
                <span class="material-symbols-outlined text-xl">history</span>
                <span>Activity Logs</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-bold text-sm transition-all {{ Route::is('admin.settings*') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300' }}"
                href="{{ route('admin.settings') }}">
                <span class="material-symbols-outlined text-xl">settings</span>
                <span>Settings</span>
            </a>
        @endif
    </nav>
    <div class="mt-auto p-4 bg-blue-50 dark:bg-blue-900/20 rounded-2xl">
        <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-2">Live Kiosk Status</p>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
            <span class="text-xs font-bold text-primary">Connected & Syncing</span>
        </div>
    </div>
</aside>

<script>
    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
    });
</script>