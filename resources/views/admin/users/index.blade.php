@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#F0F4FA] dark:bg-slate-950 transition-colors duration-300">
<!-- Admin Navbar -->
@include('admin.partials.navbar')

<main class="pl-64 pt-16 min-h-screen">
    <div class="p-12 max-w-[1400px] mx-auto">
        <header class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div>
                <h1 class="text-5xl font-black tracking-tight text-slate-800 dark:text-slate-100 mb-3">Account Management</h1>
                <p class="text-slate-500 dark:text-slate-400 text-lg">Define user roles, provision access, and maintain the campus directory.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="flex items-center gap-3 bg-slate-800 dark:bg-primary text-white px-8 py-5 rounded-[1.5rem] font-black uppercase tracking-widest text-xs shadow-xl shadow-slate-200 dark:shadow-none hover:bg-slate-900 dark:hover:bg-blue-600 hover:-translate-y-1 active:translate-y-0 transition-all group shrink-0">
                <span class="material-symbols-outlined text-[20px] transition-transform group-hover:scale-110">person_add</span>
                Create New Account
            </a>
        </header>

        @if(session('success'))
            <div class="mb-8 p-6 bg-white dark:bg-slate-900 rounded-3xl border-2 border-green-100 dark:border-green-900/30 flex items-center gap-4 animate-modal-enter shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center text-green-500">
                    <span class="material-symbols-outlined text-2xl font-black">check_circle</span>
                </div>
                <div>
                    <h4 class="font-black text-slate-800 dark:text-slate-100 tracking-tight leading-none mb-1">Success!</h4>
                    <p class="text-slate-500 dark:text-slate-400 font-medium">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="ml-auto text-slate-300 hover:text-slate-500 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        @endif

        <!-- Filter & Search Toolbar -->
        <div class="mb-8 p-6 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Search Bar -->
            <div class="relative w-full md:w-96">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">search</span>
                <input 
                    type="text" 
                    id="account-search-input"
                    placeholder="Search by name or email..."
                    class="w-full pl-12 pr-10 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-800 dark:text-slate-100 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-slate-400"
                    oninput="filterAndSortUsers()"
                >
                <button 
                    id="search-clear-btn"
                    onclick="clearSearch()"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors hidden"
                    title="Clear search"
                >
                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                </button>
            </div>

            <!-- Sorting & Role Filter Dropdowns -->
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <!-- Role Filter Dropdown -->
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 px-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <span class="material-symbols-outlined text-slate-400 text-[18px]">filter_list</span>
                    <span class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Role:</span>
                    <select 
                        id="role-filter-select"
                        onchange="filterAndSortUsers()"
                        class="bg-transparent text-sm font-bold text-slate-700 dark:text-slate-200 focus:outline-none cursor-pointer pr-2"
                    >
                        <option value="all">All Roles</option>
                        <option value="admin">Administrator</option>
                        <option value="faculty">Faculty</option>
                        <option value="principal">Principal</option>
                        <option value="guidance_counselor">Guidance Counselor</option>
                    </select>
                </div>

                <!-- Sorting Dropdown -->
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 px-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <span class="material-symbols-outlined text-slate-400 text-[18px]">sort_by_alpha</span>
                    <span class="text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Sort:</span>
                    <select 
                        id="sort-select"
                        onchange="filterAndSortUsers()"
                        class="bg-transparent text-sm font-bold text-slate-700 dark:text-slate-200 focus:outline-none cursor-pointer pr-2"
                    >
                        <option value="name_asc">Name (A → Z)</option>
                        <option value="name_desc">Name (Z → A)</option>
                        <option value="role_asc">Role (A → Z)</option>
                        <option value="newest">Newest First</option>
                    </select>
                </div>

                <!-- Results Counter Badge -->
                <span id="account-count-badge" class="px-3.5 py-2.5 bg-blue-50 dark:bg-blue-900/30 text-primary dark:text-blue-400 rounded-2xl text-xs font-black uppercase tracking-widest whitespace-nowrap">
                    {{ count($users) }} Accounts
                </span>
            </div>
        </div>

        <!-- Accounts Grid -->
        <div id="users-container" class="grid grid-cols-1 gap-6">
            @foreach($users as $index => $user)
            <div 
                class="user-card bg-white dark:bg-slate-900 p-6 rounded-[2rem] flex flex-col md:flex-row items-start md:items-center justify-between gap-6 shadow-sm border border-slate-200 dark:border-slate-800 hover:shadow-xl hover:border-primary/20 dark:hover:border-primary/40 transition-all group"
                data-name="{{ strtolower($user->name) }}"
                data-email="{{ strtolower($user->email) }}"
                data-role="{{ $user->role }}"
                data-index="{{ $index }}"
            >
                <div class="flex items-center gap-8">
                    <div class="relative">
                        <div class="w-20 h-20 rounded-[1.5rem] bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-400 dark:text-slate-500 overflow-hidden group-hover:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-5xl">account_circle</span>
                        </div>
                        <div class="absolute -bottom-2 -right-2 w-8 h-8 rounded-xl bg-white dark:bg-slate-800 border-4 border-white dark:border-slate-900 shadow-lg flex items-center justify-center">
                            <span class="w-2.5 h-2.5 rounded-full {{ $user->availability_status === 'away' ? 'bg-orange-500' : ($user->availability_status === 'busy' ? 'bg-rose-500' : 'bg-green-500') }}" title="{{ ucfirst($user->availability_status ?? 'available') }}"></span>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h4 class="text-2xl font-black text-slate-800 dark:text-slate-100 tracking-tight leading-none user-name">{{ $user->name }}</h4>
                            @if($user->id === auth()->id())
                                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 text-[9px] font-black uppercase tracking-widest rounded-md">You</span>
                            @endif
                        </div>
                        <p class="text-slate-400 dark:text-slate-500 font-medium flex items-center gap-2 user-email">
                            <span class="material-symbols-outlined text-sm">alternate_email</span>
                            {{ $user->email }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between md:justify-end gap-6 w-full md:w-auto">
                    <div class="text-left md:text-right">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-300 dark:text-slate-600 mb-2 leading-none">Time Availability</span>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center md:justify-end gap-1">
                            <span class="material-symbols-outlined text-sm text-primary">schedule</span>
                            {{ $user->time_availability ?: '8:00 AM - 5:00 PM' }}
                        </span>
                    </div>

                    <div class="text-left md:text-right">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-300 dark:text-slate-600 mb-2 leading-none">Security Role</span>
                        <span class="px-4 py-2 rounded-xl text-[10px] font-black tracking-widest uppercase shadow-sm inline-block {{ $user->role == 'admin' ? 'bg-slate-800 dark:bg-slate-700 text-white' : 'bg-blue-50 dark:bg-blue-900/20 text-primary dark:text-primary border border-blue-100 dark:border-blue-900/30' }}">
                            {{ str_replace('_', ' ', $user->role) }}
                        </span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('admin.users.edit', $user) }}" class="w-14 h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-blue-50 dark:hover:bg-blue-900/40 hover:text-primary transition-all flex items-center justify-center group/btn" title="Edit Account">
                            <span class="material-symbols-outlined text-[22px] transition-transform group-hover/btn:rotate-12">edit</span>
                        </a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this account?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-14 h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-red-50 dark:hover:bg-red-900/40 hover:text-red-500 transition-all flex items-center justify-center group/btn {{ $user->id === auth()->id() ? 'opacity-20 pointer-events-none' : '' }}" title="Delete Account">
                                <span class="material-symbols-outlined text-[22px] transition-transform group-hover/btn:scale-110">delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Empty Results State -->
        <div id="no-results-state" class="hidden py-16 text-center bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800">
            <div class="flex flex-col items-center gap-3">
                <span class="material-symbols-outlined text-5xl text-slate-300 dark:text-slate-600">person_search</span>
                <h3 class="text-xl font-black text-slate-800 dark:text-slate-100">No matching accounts found</h3>
                <p class="text-slate-400 dark:text-slate-500 text-sm max-w-sm">Try adjusting your search query or selecting a different role filter.</p>
                <button onclick="resetFilters()" class="mt-2 px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-black uppercase tracking-wider transition-all">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>
</main>
</div>

<script>
    function filterAndSortUsers() {
        const searchInput = document.getElementById('account-search-input');
        const roleFilter = document.getElementById('role-filter-select').value;
        const sortOption = document.getElementById('sort-select').value;
        const clearBtn = document.getElementById('search-clear-btn');
        const countBadge = document.getElementById('account-count-badge');
        const container = document.getElementById('users-container');
        const emptyState = document.getElementById('no-results-state');

        const query = searchInput.value.trim().toLowerCase();

        // Toggle clear button
        if (query.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }

        const cards = Array.from(document.querySelectorAll('.user-card'));
        let visibleCount = 0;

        // Filter cards
        cards.forEach(card => {
            const name = card.getAttribute('data-name') || '';
            const email = card.getAttribute('data-email') || '';
            const role = card.getAttribute('data-role') || '';

            const matchesSearch = query === '' || name.includes(query) || email.includes(query);
            const matchesRole = roleFilter === 'all' || role === roleFilter;

            if (matchesSearch && matchesRole) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Sort cards
        cards.sort((a, b) => {
            const nameA = a.getAttribute('data-name') || '';
            const nameB = b.getAttribute('data-name') || '';
            const roleA = a.getAttribute('data-role') || '';
            const roleB = b.getAttribute('data-role') || '';
            const indexA = parseInt(a.getAttribute('data-index') || '0', 10);
            const indexB = parseInt(b.getAttribute('data-index') || '0', 10);

            if (sortOption === 'name_asc') {
                return nameA.localeCompare(nameB);
            } else if (sortOption === 'name_desc') {
                return nameB.localeCompare(nameA);
            } else if (sortOption === 'role_asc') {
                return roleA.localeCompare(roleB) || nameA.localeCompare(nameB);
            } else if (sortOption === 'newest') {
                return indexB - indexA;
            }
            return 0;
        });

        // Re-append in sorted order
        cards.forEach(card => container.appendChild(card));

        // Update badge and empty state
        countBadge.textContent = `${visibleCount} Accounts`;
        if (visibleCount === 0) {
            emptyState.classList.remove('hidden');
        } else {
            emptyState.classList.add('hidden');
        }
    }

    function clearSearch() {
        const searchInput = document.getElementById('account-search-input');
        searchInput.value = '';
        filterAndSortUsers();
        searchInput.focus();
    }

    function resetFilters() {
        document.getElementById('account-search-input').value = '';
        document.getElementById('role-filter-select').value = 'all';
        document.getElementById('sort-select').value = 'name_asc';
        filterAndSortUsers();
    }
</script>
@endsection
