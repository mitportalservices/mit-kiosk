@extends('layouts.app')

@section('styles')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        :root {
            --surface: #F7F9FC;
            --on-surface: #2D3748;
            --primary: #4A90E2;
            --success: #48BB78;
            --warning: #ED8936;
            --neutral: #718096;
        }

        :root.dark {
            --surface: #0F172A;
            --on-surface: #F8FAFC;
            --primary: #3B82F6;
            --success: #10B981;
            --warning: #F59E0B;
            --neutral: #64748B;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--surface);
            color: var(--on-surface);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .font-manrope {
            font-family: 'Manrope', sans-serif;
        }

        @keyframes shimmer {
            0% {
                background-position: -600px 0
            }

            100% {
                background-position: 600px 0
            }
        }

        .skeleton {
            background: linear-gradient(90deg, #e8edf3 25%, #f5f7fa 50%, #e8edf3 75%);
            background-size: 600px 100%;
            animation: shimmer 1.4s infinite linear;
            border-radius: .75rem;
        }

        @keyframes pulse-ring {
            0% {
                box-shadow: 0 0 0 0 rgba(74, 144, 226, .45)
            }

            70% {
                box-shadow: 0 0 0 10px rgba(74, 144, 226, 0)
            }

            100% {
                box-shadow: 0 0 0 0 rgba(74, 144, 226, 0)
            }
        }

        .pulse-ring {
            animation: pulse-ring 2s ease-out infinite;
        }

        @keyframes pin-pop {
            0% {
                transform: translate(-50%, -100%) scale(0);
                opacity: 0
            }

            70% {
                transform: translate(-50%, -100%) scale(1.15);
                opacity: 1
            }

            100% {
                transform: translate(-50%, -100%) scale(1);
                opacity: 1
            }
        }

        .pin-pop {
            animation: pin-pop .25s cubic-bezier(.34, 1.56, .64, 1) forwards;
        }

        @keyframes pin-bounce {

            0%,
            100% {
                transform: translate(-50%, -100%) scale(1)
            }

            40% {
                transform: translate(-50%, -115%) scale(1.2)
            }
        }

        .pin-bounce {
            animation: pin-bounce .35s ease;
        }

        @keyframes modal-enter {
            from {
                opacity: 0;
                transform: scale(.95) translateY(12px)
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0)
            }
        }

        .modal-enter {
            animation: modal-enter .3s cubic-bezier(.34, 1.56, .64, 1) forwards;
        }

        /* Magnetic hover bounce spell */
        @keyframes pin-magnetic {

            0%,
            100% {
                transform: translate(-50%, -100%) scale(1)
            }

            50% {
                transform: translate(-50%, -108%) scale(1.08)
            }
        }

        .group:hover .pin-icon {
            animation: pin-magnetic .6s ease-in-out infinite;
        }

        @keyframes confetti-fly {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1
            }

            100% {
                transform: var(--tx) scale(0);
                opacity: 0
            }
        }

        .confetti-dot {
            position: absolute;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: confetti-fly .7s ease-out forwards;
        }

        #left-rail {
            transition: width .3s cubic-bezier(.4, 0, .2, 1);
            overflow: hidden;
        }

        .rail-label {
            transition: opacity .15s, max-width .3s;
            max-width: 160px;
            white-space: nowrap;
            overflow: hidden;
        }

        #left-rail.collapsed .rail-label {
            opacity: 0;
            max-width: 0;
        }

        #left-rail.expanded .rail-label {
            opacity: 1;
            max-width: 160px;
        }

        #nav-clock {
            font-variant-numeric: tabular-nums;
            letter-spacing: .04em;
        }
    </style>
@endsection

@section('content')
    <div class="bg-surface text-on-surface overflow-hidden">
        <!-- TopNavBar -->
        <header
            class="fixed top-0 left-0 right-0 z-50 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md shadow-sm flex justify-between items-center w-full px-8 h-16 border-b border-slate-100 dark:border-slate-800 transition-all duration-300">
            <div class="flex items-center gap-4">
                <div
                    class="w-10 h-10 bg-[#4A90E2] rounded-[0.9rem] flex items-center justify-center shrink-0 shadow-lg shadow-blue-500/20 dark:shadow-none">
                    <span class="material-symbols-outlined text-white text-[22px]"
                        style="font-variation-settings:'FILL' 1">school</span>
                </div>
                <div class="flex flex-col leading-none">
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-0.5">Campus Guide</span>
                    <span
                        class="text-xl font-black text-slate-800 dark:text-white font-manrope tracking-tight leading-none">{{ config('app.name', 'Institutional Portal') }}</span>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <button onclick="toggleTheme()"
                    class="w-10 h-10 flex items-center justify-center bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-full hover:bg-white dark:hover:bg-slate-700 hover:text-primary transition-all border border-slate-100 dark:border-slate-700 shadow-sm"
                    aria-label="Toggle Theme">
                    <span class="material-symbols-outlined text-[20px] dark:hidden">dark_mode</span>
                    <span class="material-symbols-outlined text-[20px] hidden dark:block">light_mode</span>
                </button>

                <div
                    class="hidden md:flex items-center gap-2.5 px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-full border border-slate-100 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-xs font-black uppercase tracking-widest">
                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                    <span id="nav-clock"></span>
                </div>
                <a href="{{ route('login') }}" title="Staff Login"
                    class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-xl hover:bg-white hover:text-[#4A90E2] hover:shadow-md active:scale-90 transition-all border border-slate-200/50 dark:border-slate-700"
                    aria-label="Staff Login">
                    <span class="material-symbols-outlined text-[22px]">admin_panel_settings</span>
                </a>
            </div>
        </header>
        <!-- SideNavBar (collapsible icon rail) -->
        <aside id="left-rail"
            class="collapsed fixed left-0 top-16 bottom-0 flex flex-col border-r border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl shadow-2xl z-40 w-[72px] hover:w-64 transition-all duration-300"
            onmouseenter="this.classList.replace('collapsed','expanded')"
            onmouseleave="this.classList.replace('expanded','collapsed')">
            <nav class="flex-1 py-8 px-3 flex flex-col gap-2">
                <button onclick="openStaffDirectory()"
                    class="w-full flex items-center gap-4 px-3 py-4 text-slate-400 hover:text-[#4A90E2] hover:bg-blue-50 dark:hover:bg-slate-800 rounded-2xl cursor-pointer transition-all active:scale-95 group"
                    title="Faculty Directory">
                    <span
                        class="material-symbols-outlined shrink-0 group-hover:scale-110 transition-transform">person_search</span>
                    <span class="rail-label text-sm font-black font-manrope uppercase tracking-widest">Faculty</span>
                </button>
                <button onclick="openGlobalConsultation()"
                    class="w-full flex items-center gap-4 px-3 py-4 text-slate-400 hover:text-[#4A90E2] hover:bg-blue-50 dark:hover:bg-slate-800 rounded-2xl cursor-pointer transition-all active:scale-95 group"
                    title="Request Consultation">
                    <span class="material-symbols-outlined shrink-0 group-hover:scale-110 transition-transform">forum</span>
                    <span class="rail-label text-sm font-black font-manrope uppercase tracking-widest">Consult</span>
                </button>
                <button onclick="highlightPlaces()"
                    class="w-full flex items-center gap-4 px-3 py-4 text-slate-400 hover:text-[#4A90E2] hover:bg-blue-50 dark:hover:bg-slate-800 rounded-2xl cursor-pointer transition-all active:scale-95 group"
                    title="Campus Places">
                    <span
                        class="material-symbols-outlined shrink-0 group-hover:scale-110 transition-transform">explore</span>
                    <span class="rail-label text-sm font-black font-manrope uppercase tracking-widest">Places</span>
                </button>
                <div class="h-px bg-slate-100 dark:bg-slate-800 my-2 mx-4"></div>
                <button onclick="openQueueAll()"
                    class="w-full flex items-center gap-4 px-3 py-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-2xl cursor-pointer transition-all active:scale-95 group"
                    title="Check Queue">
                    <span
                        class="material-symbols-outlined shrink-0 group-hover:scale-110 transition-transform">confirmation_number</span>
                    <span class="rail-label text-sm font-bold uppercase tracking-widest">Tickets</span>
                </button>

            </nav>
            <div class="px-3 mb-6 mt-auto">
                <div
                    class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700/50 flex items-center gap-3 group p-1 transition-all overflow-hidden shadow-inner">
                    <div
                        class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 shadow-sm dark:shadow-none flex items-center justify-center shrink-0 relative overflow-hidden">
                        <div class="absolute inset-0 bg-blue-400/10 animate-ping"></div>
                        <span class="material-symbols-outlined text-[#4A90E2] relative z-10 text-[22px]"
                            style="font-variation-settings:'FILL' 1">location_on</span>
                    </div>
                    <div class="rail-label pr-2">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-0.5">
                            Current Spot</p>
                        <p class="text-[12px] font-black text-slate-700 dark:text-slate-200 truncate">Main Lobby</p>
                    </div>
                </div>
            </div>
        </aside>
        <main class="fixed left-0 top-16 right-0 bottom-0 overflow-hidden bg-surface transition-colors duration-300">
            <!-- Map Canvas (100% Hero) -->
            <div class="relative w-full h-full pl-[72px]">
                <div id="map-canvas" class="w-full h-full bg-slate-100 dark:bg-slate-800 relative overflow-hidden select-none">
                    <div id="map-viewport" class="w-full h-full relative origin-center will-change-transform">
                        <img id="map-image" class="w-full h-full object-cover opacity-95 pointer-events-none"
                            alt="Campus 2D floor plan" src="{{ asset('images/campus_map_hires.png') }}">
                        <!-- Markers -->
                        <div id="waypoints-container" class="absolute inset-0 pointer-events-none"></div>
                    </div>

                    <!-- Map Skeleton (Overlay) -->
                    <div id="map-skeleton"
                        class="absolute inset-0 flex items-center justify-center bg-[#F7F9FC] dark:bg-[#0F172A] z-10 transition-opacity duration-500">
                        <div class="flex flex-col items-center gap-6">
                            <div
                                class="w-20 h-20 rounded-3xl bg-white dark:bg-slate-800 shadow-xl flex items-center justify-center text-blue-100 animate-pulse">
                                <span class="material-symbols-outlined text-6xl">map</span>
                            </div>
                            <div class="flex flex-col items-center gap-2">
                                <div class="skeleton w-48 h-4 rounded-full"></div>
                                <div class="skeleton w-32 h-3 rounded-full opacity-60"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Float Map Controls (Bottom Left) -->
                    <div class="absolute bottom-10 left-32 flex flex-col gap-4 z-20">
                        <div
                            class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-lg shadow-[0_15px_30px_rgba(0,0,0,0.08)] rounded-3xl flex flex-col overflow-hidden border border-white/50 dark:border-slate-700">
                            <button id="btn-zoom-in"
                                class="w-16 h-16 flex items-center justify-center hover:bg-blue-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-700 active:bg-blue-100 transition-all"
                                aria-label="Zoom in">
                                <span class="material-symbols-outlined text-[28px]">add</span>
                            </button>
                            <button id="btn-zoom-out"
                                class="w-16 h-16 flex items-center justify-center hover:bg-blue-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 active:bg-blue-100 transition-all"
                                aria-label="Zoom out">
                                <span class="material-symbols-outlined text-[28px]">remove</span>
                            </button>
                        </div>
                        <button id="btn-reset"
                            class="w-16 h-16 bg-[#4A90E2] text-white rounded-3xl shadow-[0_15px_30px_rgba(74,144,226,0.3)] flex items-center justify-center hover:bg-blue-500 hover:scale-105 active:scale-95 transition-all group"
                            aria-label="Reset view">
                            <span
                                class="material-symbols-outlined text-[28px] group-hover:rotate-12 transition-transform">my_location</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dynamic Sidebar for Waypoints (Initially Hidden) -->
            <div id="selection-sidebar"
                class="translate-x-full fixed right-0 top-16 bottom-0 w-96 bg-white/98 dark:bg-slate-900/98 backdrop-blur-xl border-l border-slate-200/60 dark:border-slate-800 shadow-2xl z-40 flex flex-col transform transition-transform duration-500 ease-out">
                <!-- Skeleton shown while loading -->
                <div id="sidebar-skeleton" class="absolute inset-0 flex flex-col gap-4 p-8 bg-white dark:bg-slate-900 hidden">
                    <div class="skeleton w-full h-48 rounded-2xl"></div>
                    <div class="skeleton w-2/3 h-6"></div>
                    <div class="skeleton w-full h-4"></div>
                    <div class="skeleton w-full h-16 rounded-xl"></div>
                    <div class="skeleton w-full h-16 rounded-xl"></div>
                </div>
                <div id="sidebar-image-wrap"
                    class="relative w-full h-80 overflow-hidden shrink-0">
                    <img id="sidebar-image" class="w-full h-full object-cover"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuAtXo8u9VWroetoDVkmZcwJEvX4ybXqpVDtrukRg_9P4NgnksqLVs7eW_DUYBaHyYvJDEceX_5KQB-SoUkTbIDffU2O8zZI0adxs42PfZUgEhuJLT23nRsk4wLu94go4xXUdkMM-PhUg5uGLFsT2oaiKVPtAyOROdPPxeyEAexe5lnBeU6TZVvklyQywBqBvBZQcnMjDQdekR2WBuuWyYfKs3b2yW57mDT5XgFHV_nk4ACtbAdkqzQGNAk-xIG7cQRTwFkOJL37SQ"
                        alt="Location image">
                    <!-- gradient fade for text legibility -->
                    <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-white dark:from-slate-900 to-transparent pointer-events-none"></div>
                    <!-- Back to Map arrow button overlay on top-left of image -->
                    <button onclick="hideSidebar()"
                        class="absolute top-4 left-4 w-11 h-11 bg-black/40 hover:bg-black/65 active:scale-90 backdrop-blur-md rounded-full text-white flex items-center justify-center transition-all shadow-lg z-20 group"
                        aria-label="Back to Map"
                        title="Back to Map">
                        <span class="material-symbols-outlined text-[24px] group-hover:-translate-x-0.5 transition-transform">arrow_back</span>
                    </button>
                </div>
                <div class="px-7 pb-7 flex-1 overflow-y-auto pt-6">
                    <h1 id="sidebar-name" class="text-3xl font-black text-on-surface tracking-tight mb-2">Waypoint</h1>
                    <p id="sidebar-desc" class="text-sm text-slate-700 dark:text-slate-900 leading-relaxed mb-5 font-medium">Select a point on the
                        map to see details.</p>
                    <div id="faculty-list" class="space-y-3"></div>
                    <div id="staff-message" class="mt-4 hidden p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900 rounded-2xl">
                        <p id="staff-message-title" class="text-sm font-extrabold text-amber-800 dark:text-amber-300"></p>
                        <p id="staff-message-sub" class="text-xs text-amber-700 dark:text-amber-400 mt-1"></p>
                    </div>
                    <div id="kiosk-actions" class="mt-5 space-y-4 hidden">
                        <button id="btn-consult"
                            class="w-full py-[20px] bg-gradient-to-r from-[#4A90E2] to-blue-600 text-white rounded-2xl font-black text-sm flex items-center justify-center gap-3 shadow-xl shadow-blue-200 dark:shadow-none active:scale-95 transition-all group">
                            <span class="material-symbols-outlined text-[22px]"
                                style="font-variation-settings:'FILL' 1">forum</span>
                            <span id="consult-btn-label" class="uppercase tracking-[0.2em] text-[11px]">Request
                                Consultation</span>
                        </button>
                        <button id="btn-appointment"
                            class="w-full py-[20px] bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-2xl font-black text-sm flex items-center justify-center gap-3 shadow-sm border border-slate-200 dark:border-slate-700 hover:bg-slate-50 active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-[22px]">event</span>
                            <span class="uppercase tracking-[0.2em] text-[11px]">Arrange Appointment</span>
                        </button>
                        <button id="btn-queue-window"
                            class="w-full py-[20px] bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black text-sm flex items-center justify-center gap-3 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 active:scale-95 transition-all">
                            <span class="material-symbols-outlined text-[22px]">dashboard_customize</span>
                            <span class="uppercase tracking-[0.2em] text-[11px]">Check My Number</span>
                        </button>
                    </div>
                </div>
                <div id="experience-button" class="p-6 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 hidden">
                    <button id="enter-3d-btn" onclick="enterPanorama()"
                        class="w-full py-[18px] bg-gradient-to-r from-[#4A90E2] to-blue-600 text-white rounded-2xl font-bold text-sm flex items-center justify-center gap-3 shadow-lg active:scale-95 transition-transform group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/15 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-700">
                        </div>
                        <span class="material-symbols-outlined text-xl"
                            style="font-variation-settings:'FILL' 1">view_in_ar</span>
                        <span class="uppercase tracking-widest text-xs">Enter 3D Experience</span>
                    </button>
                </div>
            </div>

            <div id="modal-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] hidden"></div>

            <div id="modal-staff-focus" class="fixed inset-0 z-[70] hidden items-center justify-center p-6">
                <div
                    class="w-full max-w-md bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden modal-enter p-8">
                    <div class="flex justify-end">
                        <button
                            class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center"
                            onclick="closeAllModals()">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="flex flex-col items-center text-center mt-2">
                        <div class="w-24 h-24 rounded-3xl bg-blue-50 dark:bg-slate-800 flex items-center justify-center text-[#4A90E2] mb-4">
                            <span class="material-symbols-outlined text-5xl">person</span>
                        </div>
                        <h2 id="focus-staff-name" class="text-3xl font-black text-slate-800 dark:text-white tracking-tight mb-1">Staff Name
                        </h2>
                        <p id="focus-staff-role" class="text-sm font-bold text-[#4A90E2] uppercase tracking-widest mb-4">
                            Faculty</p>
                        <div id="focus-staff-status-badge"
                            class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase mb-8">Available</div>

                        <div class="w-full space-y-3">
                            <button id="focus-btn-consult"
                                class="w-full py-5 bg-gradient-to-r from-[#4A90E2] to-blue-600 text-white rounded-2xl font-bold text-sm flex items-center justify-center gap-3 shadow-lg active:scale-95 transition-transform disabled:opacity-50 disabled:pointer-events-none">
                                <span class="material-symbols-outlined"
                                    style="font-variation-settings:'FILL' 1">forum</span>
                                <span class="uppercase tracking-widest text-xs">Request
                                    Consultation</span>
                            </button>
                            <button id="focus-btn-appointment"
                                class="w-full py-5 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-2xl font-bold text-sm flex items-center justify-center gap-3 shadow-sm border border-slate-200 dark:border-slate-700 active:scale-95 transition-transform">
                                <span class="material-symbols-outlined">event</span>
                                <span class="uppercase tracking-widest text-xs">Arrange Appointment</span>
                            </button>
                        </div>
                        <p id="focus-staff-msg" class="mt-6 text-xs font-bold text-amber-600 hidden">Not available for
                            consultation right now.</p>
                    </div>
                </div>
            </div>

            <div id="modal-consult" class="fixed inset-0 z-[70] hidden items-center justify-center p-6">
                <div class="w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Consultation</p>
                            <h2 class="text-2xl font-extrabold text-slate-800 dark:text-white" id="consult-title">Select purpose</h2>
                        </div>
                        <button
                            class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center"
                            onclick="closeAllModals()">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="p-8 max-h-[75vh] overflow-y-auto">
                        <div id="consult-options" class="grid grid-cols-1 gap-3 max-h-[40vh] overflow-y-auto pr-1"></div>
                        <div id="consult-other-wrap" class="mt-4 hidden">
                            <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-2">Please specify</label>
                            <textarea id="consult-other" data-osk="1" rows="3"
                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 transition-all font-medium"
                                placeholder="Type here..."></textarea>
                        </div>
                        <div class="mt-6 flex items-center justify-end gap-3">
                            <button class="px-6 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold"
                                onclick="closeAllModals()">Cancel</button>
                            <button id="consult-submit"
                                class="px-6 py-4 rounded-2xl bg-gradient-to-r from-[#4A90E2] to-blue-700 text-white font-bold shadow-xl">Get
                                Ticket</button>
                        </div>
                        <p id="consult-error" class="mt-4 text-sm font-bold text-red-500 hidden flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">warning</span>
                            <span class="msg"></span>
                        </p>
                    </div>
                </div>
            </div>

            <div id="modal-appointment" class="fixed inset-0 z-[70] hidden items-center justify-center p-6">
                <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Appointment Request</p>
                            <h2 class="text-2xl font-extrabold text-slate-800 dark:text-white">Fill up details</h2>
                        </div>
                        <button
                            class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center"
                            onclick="closeAllModals()">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="p-8 grid grid-cols-1 gap-6 max-h-[75vh] overflow-y-auto">
                        <div>
                            <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-2">Who are you?</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button type="button"
                                    class="type-btn px-4 py-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 font-bold text-slate-800 dark:text-slate-200"
                                    data-type="student">Student</button>
                                <button type="button"
                                    class="type-btn px-4 py-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 font-bold text-slate-800 dark:text-slate-200"
                                    data-type="parent">Parent</button>
                                <button type="button"
                                    class="type-btn px-4 py-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 font-bold text-slate-800 dark:text-slate-200"
                                    data-type="visitor">Visitor</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-3">
                                <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-2">Honorific</label>
                                <select id="appt-honorific"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 transition-all font-medium appearance-none">
                                    <option value="Mr.">Mr.</option>
                                    <option value="Ms.">Ms.</option>
                                    <option value="Mx.">Mx.</option>
                                    <option value="Dr.">Dr.</option>
                                    <option value="Prof.">Prof.</option>
                                </select>
                            </div>
                            <div class="md:col-span-9">
                                <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-2">Full Name</label>
                                <input id="appt-name" data-osk="1" type="text"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 transition-all font-medium"
                                    placeholder="Your full name">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-2">Email Address</label>
                            <input id="appt-email" data-osk="1" type="email"
                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 transition-all font-medium"
                                placeholder="you@example.com">
                        </div>

                        <div id="appt-grade-wrap" class="hidden">
                            <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-2">Grade Level</label>
                            <select id="appt-grade"
                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 transition-all font-medium appearance-none">
                                <option value="">Select grade</option>
                                <option>Grade 1</option>
                                <option>Grade 2</option>
                                <option>Grade 3</option>
                                <option>Grade 4</option>
                                <option>Grade 5</option>
                                <option>Grade 6</option>
                                <option>Grade 7</option>
                                <option>Grade 8</option>
                                <option>Grade 9</option>
                                <option>Grade 10</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-2">Purpose</label>
                            <div id="appt-options"
                                class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[40vh] overflow-y-auto pr-1"></div>
                            <div id="appt-other-wrap" class="mt-4 hidden">
                                <label class="block text-sm font-bold text-slate-600 dark:text-slate-400 mb-2">Please specify</label>
                                <textarea id="appt-other" data-osk="1" rows="3"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 transition-all font-medium"
                                    placeholder="Type here..."></textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button class="px-6 py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold"
                                onclick="closeAllModals()">Cancel</button>
                            <button id="appt-submit"
                                class="px-6 py-4 rounded-2xl bg-gradient-to-r from-[#4A90E2] to-blue-700 text-white font-bold shadow-xl">Submit
                                Request</button>
                        </div>
                        <p id="appt-error" class="text-sm font-bold text-red-500 hidden flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">warning</span>
                            <span class="msg"></span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Appointment Success Modal -->
            <div id="modal-appt-success" class="fixed inset-0 z-[70] hidden items-center justify-center p-6">
                <div
                    class="w-full max-w-md bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden modal-enter p-10 text-center">
                    <div
                        class="w-24 h-24 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-500 mx-auto mb-6">
                        <span class="material-symbols-outlined text-6xl"
                            style="font-variation-settings:'FILL' 1">check_circle</span>
                    </div>
                    <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight mb-2">Request Sent!</h2>
                    <p class="text-slate-500 dark:text-slate-400 font-medium mb-8">Your appointment request has been submitted. Please check
                        your email for updates.</p>
                    <button
                        class="w-full py-5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-lg shadow-emerald-100 active:scale-95 transition-all"
                        onclick="openFeedbackModal()">
                        Done
                    </button>
                </div>
            </div>

            <div id="modal-ticket" class="fixed inset-0 z-[70] hidden items-center justify-center p-6">
                <div
                    class="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden modal-enter">
                    <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Ticket</p>
                            <h2 class="text-2xl font-extrabold text-slate-800 dark:text-white">Please keep this number</h2>
                        </div>
                        <button
                            class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center"
                            onclick="finishTicket()">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="p-8 relative text-center">
                        <div id="confetti-host" class="absolute inset-0 pointer-events-none overflow-hidden"></div>
                        <div class="flex justify-center mb-4">
                            <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                                <span class="material-symbols-outlined text-4xl text-emerald-500"
                                    style="font-variation-settings:'FILL' 1">check_circle</span>
                            </div>
                        </div>
                        <div id="receipt"
                            class="p-6 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-center">
                            <div class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Consultation Number
                            </div>
                            <div class="mt-4 border-t border-dashed border-slate-300 dark:border-slate-700"></div>
                            <div id="ticket-number" class="mt-4 text-9xl font-black tracking-tight text-slate-900 dark:text-white"
                                style="font-weight:900;font-family:'Arial Black', Impact, sans-serif;font-size:144px;line-height:0.95;letter-spacing:-2px;">
                                000</div>
                            <div class="mt-4 border-t border-dashed border-slate-300 dark:border-slate-700"></div>
                            <div id="ticket-meta"
                                class="mt-4 text-[10px] font-black uppercase tracking-widest text-slate-400"></div>
                        </div>
                        <p id="ticket-print-error" class="mt-4 text-xs font-bold text-amber-600 hidden"></p>
                        <div class="mt-6 space-y-3">
                            <button
                                class="w-full py-5 rounded-2xl bg-gradient-to-r from-[#4A90E2] to-blue-600 text-white font-black uppercase tracking-widest text-xs shadow-lg active:scale-95 transition-all"
                                onclick="finishTicket()">Done</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Modal -->
            <div id="modal-feedback" class="fixed inset-0 z-[70] hidden items-center justify-center p-6">
                <div class="w-full max-w-lg bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden modal-enter p-8 text-center">
                    
                    <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 mx-auto mb-4">
                        <span class="material-symbols-outlined text-3xl">rate_review</span>
                    </div>

                    <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight mb-1">We want to hear your feedback!</h2>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-6">How would you rate your experience today?</p>

                    <form id="feedback-form" onsubmit="event.preventDefault(); submitFeedback();" class="space-y-6">
                        <!-- Rating Options (Radio buttons with Emojis & small text) -->
                        <div class="grid grid-cols-5 gap-2">
                            <label class="group relative cursor-pointer">
                                <input type="radio" name="rating" value="Very Satisfied" class="peer sr-only" required>
                                <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border-2 border-slate-100 dark:border-slate-700/60 peer-checked:border-blue-500 peer-checked:bg-blue-50/50 dark:peer-checked:bg-blue-900/20 transition-all flex flex-col items-center gap-1 group-hover:scale-105">
                                    <span class="text-3xl">😄</span>
                                    <span class="text-[9px] font-extrabold text-slate-600 dark:text-slate-300 leading-tight">Very Satisfied</span>
                                </div>
                            </label>

                            <label class="group relative cursor-pointer">
                                <input type="radio" name="rating" value="Satisfied" class="peer sr-only">
                                <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border-2 border-slate-100 dark:border-slate-700/60 peer-checked:border-blue-500 peer-checked:bg-blue-50/50 dark:peer-checked:bg-blue-900/20 transition-all flex flex-col items-center gap-1 group-hover:scale-105">
                                    <span class="text-3xl">🙂</span>
                                    <span class="text-[9px] font-extrabold text-slate-600 dark:text-slate-300 leading-tight">Satisfied</span>
                                </div>
                            </label>

                            <label class="group relative cursor-pointer">
                                <input type="radio" name="rating" value="Neutral" class="peer sr-only">
                                <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border-2 border-slate-100 dark:border-slate-700/60 peer-checked:border-blue-500 peer-checked:bg-blue-50/50 dark:peer-checked:bg-blue-900/20 transition-all flex flex-col items-center gap-1 group-hover:scale-105">
                                    <span class="text-3xl">😐</span>
                                    <span class="text-[9px] font-extrabold text-slate-600 dark:text-slate-300 leading-tight">Neutral</span>
                                </div>
                            </label>

                            <label class="group relative cursor-pointer">
                                <input type="radio" name="rating" value="Dissatisfied" class="peer sr-only">
                                <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border-2 border-slate-100 dark:border-slate-700/60 peer-checked:border-blue-500 peer-checked:bg-blue-50/50 dark:peer-checked:bg-blue-900/20 transition-all flex flex-col items-center gap-1 group-hover:scale-105">
                                    <span class="text-3xl">🙁</span>
                                    <span class="text-[9px] font-extrabold text-slate-600 dark:text-slate-300 leading-tight">Dissatisfied</span>
                                </div>
                            </label>

                            <label class="group relative cursor-pointer">
                                <input type="radio" name="rating" value="Very Dissatisfied" class="peer sr-only">
                                <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border-2 border-slate-100 dark:border-slate-700/60 peer-checked:border-blue-500 peer-checked:bg-blue-50/50 dark:peer-checked:bg-blue-900/20 transition-all flex flex-col items-center gap-1 group-hover:scale-105">
                                    <span class="text-3xl">😞</span>
                                    <span class="text-[9px] font-extrabold text-slate-600 dark:text-slate-300 leading-tight">Very Dissatisfied</span>
                                </div>
                            </label>
                        </div>

                        <!-- Suggestions Textarea -->
                        <div class="text-left">
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-2">Do you have any suggestions on how we can improve our kiosk?</label>
                            <textarea id="feedback-suggestions" data-osk="1" rows="3"
                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 transition-all font-medium text-sm"
                                placeholder="(optional)"></textarea>
                        </div>

                        <p id="feedback-error" class="text-sm font-bold text-red-500 hidden flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">warning</span>
                            <span class="msg">Please select a rating option.</span>
                        </p>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button type="button"
                                class="w-full py-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-black uppercase tracking-widest text-xs active:scale-95 transition-all"
                                onclick="closeAllModals()">
                                Skip
                            </button>
                            <button type="submit" id="feedback-submit"
                                class="w-full py-4 rounded-2xl bg-gradient-to-r from-[#4A90E2] to-blue-600 text-white font-black uppercase tracking-widest text-xs shadow-lg shadow-blue-200 dark:shadow-none active:scale-95 transition-all">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Faculty Directory Modal -->
            <div id="modal-staff-directory" class="fixed inset-0 z-[70] hidden items-center justify-center p-6">
                <div
                    class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden modal-enter flex flex-col max-h-[85vh]">
                    <div class="px-10 py-8 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                        <div>
                            <p class="text-xs font-black text-[#4A90E2] uppercase tracking-[0.2em] mb-1">Institution
                                Directory</p>
                            <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight">Faculty & Staff</h2>
                        </div>
                        <button
                            class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 flex items-center justify-center shadow-sm transition-all active:scale-90"
                            onclick="closeAllModals()">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="p-8 overflow-y-auto custom-scrollbar flex-1">
                        <div id="directory-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Staff cards injected here -->
                        </div>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-800 border-t border-slate-100 dark:border-slate-800 text-center">
                        <p class="text-xs font-bold text-slate-400">Can't find who you're looking for? Visit the Main
                            Office.</p>
                    </div>
                </div>
            </div>
        </main>

        <div id="osk" class="fixed left-0 right-0 bottom-0 z-[100] hidden">
        <div class="mx-auto max-w-5xl px-4 pb-4">
            <div class="bg-slate-200/90 dark:bg-slate-900/98 backdrop-blur-2xl border border-slate-300 dark:border-slate-800 rounded-3xl shadow-2xl p-4 transition-colors duration-300">
                <div class="flex items-center justify-between px-2 pb-2">
                    <div class="text-xs font-black uppercase tracking-widest text-slate-400">On-screen Keyboard</div>
                    <button id="osk-close" type="button" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center">
                        <span class="material-symbols-outlined">keyboard_hide</span>
                    </button>
                </div>
                <!-- Typed text preview -->
                <div id="osk-preview" class="mx-2 mb-2 px-4 py-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl text-sm text-slate-700 dark:text-slate-200 font-medium min-h-[36px] truncate text-right border border-slate-100 dark:border-slate-700 transition-colors"></div>
                <div id="osk-keys" class="grid gap-2"></div>
            </div>
        </div>
    </div>

    <script type="application/json" id="kiosk-waypoints-json">{!! json_encode($waypoints) !!}</script>
    <script type="application/json" id="staff-members-json">{!! json_encode($staffMembers) !!}</script>

    <script>
        // ── Theme Management ──
        function toggleTheme() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // ── Live Clock ──
        (function() {
            const el = document.getElementById('nav-clock');
            function tick() {
                const now = new Date();
                el.textContent = now.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'});
            }
            tick(); setInterval(tick, 1000);
        })();

        let waypoints = JSON.parse(document.getElementById('kiosk-waypoints-json')?.textContent || '[]');

        // ── Map Interactivity (Pan & Zoom) ──
        (function() {
            const viewport = document.getElementById('map-viewport');
            const canvas = document.getElementById('map-canvas');
            if (!viewport || !canvas) return;

            let scale = 1, translateX = 0, translateY = 0;
            let isDragging = false, startX, startY;

            function updateTransform() {
                viewport.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
            }

            canvas.addEventListener('mousedown', (e) => {
                if (e.button !== 0) return;
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                canvas.style.cursor = 'grabbing';
            });

            window.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                translateX = e.clientX - startX;
                translateY = e.clientY - startY;
                updateTransform();
            });

            window.addEventListener('mouseup', () => {
                isDragging = false;
                canvas.style.cursor = 'grab';
            });

            canvas.addEventListener('wheel', (e) => {
                e.preventDefault();
                const delta = e.deltaY > 0 ? 0.9 : 1.1;
                const nextScale = Math.min(Math.max(scale * delta, 0.5), 5);

                const rect = canvas.getBoundingClientRect();
                const mouseX = e.clientX - rect.left;
                const mouseY = e.clientY - rect.top;

                const worldX = (mouseX - translateX) / scale;
                const worldY = (mouseY - translateY) / scale;

                translateX = mouseX - worldX * nextScale;
                translateY = mouseY - worldY * nextScale;
                scale = nextScale;

                updateTransform();
            }, { passive: false });

            // Controls
            document.getElementById('btn-zoom-in').addEventListener('click', () => {
                scale = Math.min(scale * 1.3, 5);
                updateTransform();
            });
            document.getElementById('btn-zoom-out').addEventListener('click', () => {
                scale = Math.max(scale / 1.3, 0.5);
                updateTransform();
            });
            document.getElementById('btn-reset').addEventListener('click', () => {
                scale = 1; translateX = 0; translateY = 0;
                updateTransform();
            });

            // Touch support (basic pan)
            canvas.addEventListener('touchstart', (e) => {
                if (e.touches.length === 1) {
                    isDragging = true;
                    startX = e.touches[0].clientX - translateX;
                    startY = e.touches[0].clientY - translateY;
                }
            }, { passive: true });

            canvas.addEventListener('touchmove', (e) => {
                if (!isDragging || e.touches.length !== 1) return;
                translateX = e.touches[0].clientX - startX;
                translateY = e.touches[0].clientY - startY;
                updateTransform();
            }, { passive: true });

            canvas.addEventListener('touchend', () => { isDragging = false; });
        })();

        // Dismiss map skeleton once waypoints are rendered
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const sk = document.getElementById('map-skeleton');
                if (sk) sk.remove();
            }, 400);
        });

        let currentWaypoint = null;
        let currentStaff = null;
        let selectedConsultPurpose = null;
        let selectedApptPurpose = null;
        let selectedApptType = 'student';

        const PURPOSES = {
            faculty: [
                { code: 'Assignment and coursework consultation' },
                { code: 'Lesson clarification and academic assistance' },
                { code: 'Assessment review (quiz, test, or exam)' },
                { code: 'Submission and completion of academic requirements' },
                { code: 'Academic performance discussion' },
                { code: 'Other Academic Concerns (please specify)', other: true },
            ],
            guidance_counselor: [
                { code: 'Personal and emotional counseling' },
                { code: 'Mental health and well-being support' },
                { code: 'Academic and career guidance' },
                { code: 'Peer conflict and relationship concerns' },
                { code: 'Student adjustment and support services' },
                { code: 'Other Counseling Concerns (please specify)', other: true },
            ],
            principal: [
                { code: 'Reporting of serious incidents or concerns' },
                { code: 'Formal complaints and policy-related matters' },
                { code: 'Requests for approval or special permission' },
                { code: 'Disciplinary consultations' },
                { code: 'Administrative and escalated concerns' },
                { code: 'Other Administrative Concerns (please specify)', other: true },
            ],
        };

        function roleLabel(role) {
            if (role === 'guidance_counselor') return 'Guidance Counselor';
            if (role === 'principal') return 'Principal';
            return 'Faculty';
        }

        function openModal(id) {
            document.getElementById('modal-backdrop').classList.remove('hidden');
            const el = document.getElementById(id);
            el.classList.remove('hidden');
            el.classList.add('flex');
            // re-trigger modal-enter animation
            const inner = el.querySelector('.modal-enter, [class*="rounded-3xl"]');
            if (inner) { inner.style.animation='none'; void inner.offsetWidth; inner.style.animation=''; }
        }

        function closeAllModals() {
            document.getElementById('modal-backdrop').classList.add('hidden');
            ['modal-staff-focus', 'modal-consult', 'modal-appointment', 'modal-ticket', 'modal-appt-success', 'modal-staff-directory', 'modal-feedback'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                }
            });
            document.getElementById('consult-error').classList.add('hidden');
            document.getElementById('appt-error').classList.add('hidden');
            if (document.getElementById('feedback-error')) {
                document.getElementById('feedback-error').classList.add('hidden');
            }
            // Do NOT clear currentWaypoint/currentStaff here as they are needed for flow
        }

        function openFeedbackModal() {
            closeAllModals();
            const form = document.getElementById('feedback-form');
            if (form) form.reset();
            const errorEl = document.getElementById('feedback-error');
            if (errorEl) errorEl.classList.add('hidden');
            openModal('modal-feedback');
        }

        async function submitFeedback() {
            const selectedRating = document.querySelector('input[name="rating"]:checked');
            const suggestions = document.getElementById('feedback-suggestions')?.value || '';
            const errorEl = document.getElementById('feedback-error');

            if (!selectedRating) {
                if (errorEl) {
                    errorEl.querySelector('.msg').textContent = 'Please select a rating.';
                    errorEl.classList.remove('hidden');
                }
                return;
            }

            if (errorEl) errorEl.classList.add('hidden');

            const submitBtn = document.getElementById('feedback-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            }

            try {
                const response = await fetch('/api/kiosk/feedback', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        rating: selectedRating.value,
                        suggestions: suggestions
                    })
                });

                if (response.ok) {
                    closeAllModals();
                } else {
                    const data = await response.json();
                    if (errorEl) {
                        errorEl.querySelector('.msg').textContent = data.message || 'Failed to submit feedback.';
                        errorEl.classList.remove('hidden');
                    }
                }
            } catch (err) {
                if (errorEl) {
                    errorEl.querySelector('.msg').textContent = 'Network error. Please try again.';
                    errorEl.classList.remove('hidden');
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit';
                }
            }
        }

        let staffMembers = JSON.parse(document.getElementById('staff-members-json')?.textContent || '[]');

        function showStaffFocus(staffId) {
            const staff = staffMembers.find(s => s.id == staffId);
            if (!staff) return;

            currentStaff = staff;
            refreshStaffFocus();
            openModal('modal-staff-focus');
        }

        function refreshStaffFocus() {
            if (!currentStaff) return;
            const staff = staffMembers.find(s => s.id == currentStaff.id) || currentStaff;
            currentStaff = staff;

            document.getElementById('focus-staff-name').textContent = staff.name;
            document.getElementById('focus-staff-role').textContent = roleLabel(staff.role);

            const badge = document.getElementById('focus-staff-status-badge');
            badge.textContent = staff.availability_status || 'unknown';
            badge.className = `px-4 py-1.5 rounded-full text-[10px] font-black uppercase mb-8 ${getStatusBg(staff.availability_status)}`;

            const focusConsultBtn = document.getElementById('focus-btn-consult');
            const focusConsultLabel = document.getElementById('focus-consult-label');
            const canConsult = (staff.availability_status === 'available');

            focusConsultBtn.disabled = !canConsult;
            if (focusConsultLabel) focusConsultLabel.textContent = canConsult ? `Consultation with ${staff.name.split(' ')[0]}` : 'Request Consultation';

            const focusMsg = document.getElementById('focus-staff-msg');
            focusMsg.classList.toggle('hidden', canConsult);

            setupConsultOptions(staff.role);
            setupApptOptions(staff.role);
        }

        function openStaffDirectory() {
            const list = document.getElementById('directory-list');
            list.innerHTML = '';
            staffMembers.forEach(staff => {
                const card = document.createElement('button');
                card.className = 'flex items-center gap-4 p-5 rounded-3xl bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-slate-700 border border-slate-100 dark:border-slate-700 hover:border-blue-200 transition-all text-left group';
                card.onclick = () => {
                    closeAllModals();
                    showStaffFocus(staff.id);
                };
                card.innerHTML = `
                    <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-900 shadow-sm flex items-center justify-center text-slate-300 dark:text-slate-600 group-hover:text-[#4A90E2] transition-colors">
                        <span class="material-symbols-outlined text-3xl">person</span>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-800 dark:text-white tracking-tight leading-none mb-1">${staff.name}</h4>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">${roleLabel(staff.role)}</p>
                    </div>
                `;
                list.appendChild(card);
            });
            openModal('modal-staff-directory');
        }

        function openGlobalConsultation() {
            // Find first available staff or show directory
            openStaffDirectory();
        }

        function highlightPlaces() {
            // Visual cue: Reset zoom and bounce all pins
            document.getElementById('btn-reset').click();
            const pins = document.querySelectorAll('.pin-pop');
            pins.forEach((p, i) => {
                setTimeout(() => {
                    p.classList.remove('pin-bounce');
                    void p.offsetWidth;
                    p.classList.add('pin-bounce');
                }, i * 50);
            });
        }

        function openQueueAll() {
            window.open('/kiosk/queue', '_blank');
        }

        // ── Confetti burst ──
        function fireConfetti() {
            const host = document.getElementById('confetti-host');
            if (!host) return;
            host.innerHTML = '';
            const colors = ['#4A90E2','#48BB78','#ED8936','#FC8181','#9F7AEA','#F6E05E'];
            for (let i = 0; i < 18; i++) {
                const dot = document.createElement('div');
                dot.className = 'confetti-dot';
                const angle = (i / 18) * 360;
                const dist = 80 + Math.random() * 60;
                const tx = `translate(${Math.cos(angle*Math.PI/180)*dist}px, ${Math.sin(angle*Math.PI/180)*dist}px)`;
                dot.style.setProperty('--tx', tx);
                dot.style.background = colors[i % colors.length];
                dot.style.left = '50%'; dot.style.top = '40%';
                host.appendChild(dot);
            }
        }

        function ticketOffsetForRole(role) {
            if (role === 'principal') return 100;
            if (role === 'guidance_counselor') return 200;
            return 0;
        }

        function formatTicketNumber(raw, role) {
            const n = Number(raw || 0);
            const offset = ticketOffsetForRole(role);
            return String(n + offset).padStart(3, '0');
        }

        // Ticket waiting to be printed. It is printed only when the student presses "Done".
        let pendingTicketPrint = null;

        function finishTicket() {
            const job = pendingTicketPrint;
            pendingTicketPrint = null; // guarantee a single print per ticket
            if (job) printTicket(job);
            openFeedbackModal();
        }

        function fetchWithTimeout(url, options, ms) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), ms);
            return fetch(url, Object.assign({}, options || {}, { signal: controller.signal }))
                .finally(() => clearTimeout(timeoutId));
        }

        // Sends the ticket to the local Kiosk PC print bridge (Launch-Kiosk.bat, port 18181),
        // which prints straight to the POS-58 thermal printer with NO print dialog.
        // Returns 'printed', 'failed' (bridge is running but the printer reported an error)
        // or 'offline' (no bridge reachable).
        async function sendToLocalPrintBridge(payload) {
            const body = JSON.stringify({ display_number: payload.displayNumber, meta: payload.meta });
            const isLocalHost = ['127.0.0.1', 'localhost'].includes(window.location.hostname);
            const targets = [{ health: 'http://127.0.0.1:18181', print: 'http://127.0.0.1:18181' }];
            if (isLocalHost) targets.push({ health: null, print: '/api/kiosk/print' });

            let lastError = null;
            for (const t of targets) {
                // 1) Quick health check so an offline bridge is detected fast.
                if (t.health) {
                    try {
                        const h = await fetchWithTimeout(t.health, { method: 'GET' }, 1500);
                        if (!h.ok) continue;
                    } catch (e) {
                        continue;
                    }
                }
                // 2) Bridge is online: give the printer enough time (the Windows spooler /
                //    PowerShell path can take a few seconds). A short timeout here used to abort
                //    a print that was still in progress and then open the browser print dialog.
                try {
                    const res = await fetchWithTimeout(t.print, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body,
                    }, 15000);
                    const data = await res.json().catch(() => null);
                    const ok = data && ((data.print && data.print.ok === true) || data.ok === true);
                    if (ok) return { status: 'printed' };
                    lastError = (data && (data.error || (data.print && data.print.error))) || 'Printer error.';
                } catch (e) {
                    lastError = 'Print bridge did not respond in time.';
                }
            }
            return lastError ? { status: 'failed', error: lastError } : { status: 'offline' };
        }

        async function printTicket(payload) {
            const errEl = document.getElementById('ticket-print-error');
            if (errEl) errEl.classList.add('hidden');

            const result = await sendToLocalPrintBridge(payload);
            if (result.status === 'printed') return;

            if (result.status === 'failed') {
                // The bridge is running, so do NOT open the browser dialog (it could also print twice).
                console.warn('[Kiosk] Thermal print failed:', result.error);
                if (errEl) {
                    errEl.textContent = 'Printer error: ' + String(result.error);
                    errEl.classList.remove('hidden');
                }
                return;
            }

            // Bridge offline: use browser printing. When Chrome is started by Launch-Kiosk.bat
            // (dedicated profile + --kiosk-printing) this prints silently to the default printer.
            const browserFallbackEnabled = {{ config('printer.browser_print_fallback', true) ? 'true' : 'false' }};
            if (!browserFallbackEnabled) {
                console.warn('[Kiosk] Local print bridge is offline and browser printing is disabled.');
                return;
            }

            printTicketBrowser(payload.displayNumber, payload.meta);
        }

        function escapeTicketHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Browser-printed ticket laid out for 57mm thermal paper (~47mm printable width).
        function printTicketBrowser(displayNumber, meta) {
            const cleanMeta = String(meta || '').replace(/\s*[•·|]\s*|\s+-\s+/g, ' - ');
            const parts = cleanMeta.split(' - ').map(s => s.trim()).filter(Boolean);
            const staff = escapeTicketHtml((parts[0] || meta || '').toUpperCase());
            const loc = escapeTicketHtml(parts.slice(1).join(' - ').toUpperCase());

            // Spaced number (e.g., "1 0 1") to match the ESC/POS thermal layout
            const rawNum = String(displayNumber || '').trim();
            const spacedNum = escapeTicketHtml((rawNum.length <= 4) ? rawNum.split('').join(' ') : rawNum);

            const now = new Date();
            const timeStr = now.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }) + '  ' +
                            now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

            const html = `
    <!doctype html>
    <html>
      <head>
        <meta charset="utf-8" />
        <title>Consultation Ticket</title>
        <style>
          @page {
            size: 57mm auto;
            margin: 0;
          }
          * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
          }
          html, body {
            width: 57mm;
            background: #fff;
          }
          body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
          .ticket {
            width: 46mm;
            margin: 0 auto;
            padding: 2mm 0 8mm 0;
            overflow: hidden;
          }
          .rule {
            border-top: 1.5px dashed #000;
            margin: 1.5mm 0;
          }
          .header-label {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.4px;
          }
          .number {
            font-family: 'Courier New', Courier, monospace;
            font-size: 34px;
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: 1px;
            margin: 1mm 0;
            white-space: nowrap;
          }
          .staff {
            font-size: 11px;
            font-weight: 900;
            line-height: 1.2;
            overflow-wrap: anywhere;
          }
          .location {
            font-size: 9.5px;
            font-weight: 700;
            line-height: 1.2;
            margin-top: 0.5mm;
            overflow-wrap: anywhere;
          }
          .footer-msg {
            font-size: 9px;
            font-weight: 700;
          }
          .footer-date {
            font-size: 8.5px;
            font-weight: 600;
            margin-top: 0.5mm;
          }
        </style>
      </head>
      <body>
        <div class="ticket">
          <div class="rule"></div>
          <div class="header-label">CONSULTATION NUMBER</div>
          <div class="rule"></div>
          <div class="number">${spacedNum}</div>
          <div class="rule"></div>
          <div class="staff">${staff}</div>
          ${loc ? `<div class="location">${loc}</div>` : ''}
          <div class="rule"></div>
          <div class="footer-msg">Please keep this number</div>
          <div class="footer-date">${timeStr}</div>
        </div>
      </body>
    </html>`;

            const frame = document.createElement('iframe');
            frame.style.position = 'fixed';
            frame.style.right = '0';
            frame.style.bottom = '0';
            frame.style.width = '0';
            frame.style.height = '0';
            frame.style.border = '0';
            document.body.appendChild(frame);

            frame.onload = () => {
                setTimeout(() => {
                    try {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    } finally {
                        setTimeout(() => frame.remove(), 800);
                    }
                }, 50);
            };

            if ('srcdoc' in frame) {
                frame.srcdoc = html;
            } else {
                const doc = frame.contentWindow.document;
                doc.open();
                doc.write(html);
                doc.close();
            }
        }

        function retryBrowserPrint() {
            const displayNumber = document.getElementById('ticket-number')?.textContent || '';
            const meta = document.getElementById('ticket-meta')?.textContent || '';
            printTicketBrowser(displayNumber, meta);
        }

        async function retryBackendPrint() {
            const errEl = document.getElementById('ticket-print-error');
            if (errEl) errEl.classList.add('hidden');

            const displayNumber = document.getElementById('ticket-number')?.textContent || '';
            const meta = document.getElementById('ticket-meta')?.textContent || '';

            try {
                const res = await fetch('/api/kiosk/print', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ display_number: String(displayNumber).trim(), meta: String(meta).trim() }),
                });

                const data = await res.json();
                const ok = data && data.print && data.print.ok === true;
                if (ok) return;

                const msg = data && data.print && data.print.error
                    ? 'Printer error: ' + String(data.print.error)
                    : 'Printing failed on the kiosk PC.';
                if (errEl) {
                    errEl.textContent = msg;
                    errEl.classList.remove('hidden');
                }
            } catch (e) {
                if (errEl) {
                    errEl.textContent = 'Network error while printing. Please try again.';
                    errEl.classList.remove('hidden');
                }
            }
        }

        function setupConsultOptions(role) {
            const list = PURPOSES[role] || PURPOSES.faculty;
            selectedConsultPurpose = null;
            document.getElementById('consult-other').value = '';
            document.getElementById('consult-other-wrap').classList.add('hidden');
            const container = document.getElementById('consult-options');
            container.innerHTML = '';
            list.forEach((p) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'px-5 py-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-left font-bold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors';
                btn.textContent = p.code;
                btn.addEventListener('click', () => {
                    selectedConsultPurpose = p;
                    [...container.children].forEach((c) => c.classList.remove('ring-2', 'ring-primary/30'));
                    btn.classList.add('ring-2', 'ring-primary/30');
                    if (p.other) {
                        document.getElementById('consult-other-wrap').classList.remove('hidden');
                    } else {
                        document.getElementById('consult-other-wrap').classList.add('hidden');
                    }
                });
                container.appendChild(btn);
            });
        }

        function setupApptOptions(role) {
            const list = PURPOSES[role] || PURPOSES.faculty;
            selectedApptPurpose = null;
            document.getElementById('appt-other').value = '';
            document.getElementById('appt-other-wrap').classList.add('hidden');
            const container = document.getElementById('appt-options');
            container.innerHTML = '';
            list.forEach((p) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'px-5 py-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-left font-bold hover:bg-white dark:hover:bg-slate-700 hover:shadow-md transition-all flex items-center gap-3 group';
                btn.innerHTML = `
                    <div class="w-8 h-8 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center shrink-0 group-hover:border-blue-200">
                        <span class="material-symbols-outlined text-[18px] text-slate-400 group-hover:text-blue-400">check</span>
                    </div>
                    <span class="text-sm text-slate-700 dark:text-slate-200 leading-tight">${p.code}</span>
                `;
                btn.addEventListener('click', () => {
                    selectedApptPurpose = p;
                    [...container.children].forEach((c) => {
                        c.classList.remove('ring-2', 'ring-primary', 'bg-blue-50/50', 'border-blue-200');
                        c.querySelector('div').classList.remove('bg-blue-600', 'border-blue-600');
                        c.querySelector('span.material-symbols-outlined').classList.remove('text-white');
                        c.querySelector('span.material-symbols-outlined').classList.add('text-slate-400');
                    });
                    btn.classList.add('ring-2', 'ring-primary', 'bg-blue-50/50', 'border-blue-200');
                    btn.querySelector('div').classList.add('bg-blue-600', 'border-blue-600');
                    btn.querySelector('span.material-symbols-outlined').classList.remove('text-slate-400');
                    btn.querySelector('span.material-symbols-outlined').classList.add('text-white');

                    if (p.other) {
                        document.getElementById('appt-other-wrap').classList.remove('hidden');
                    } else {
                        document.getElementById('appt-other-wrap').classList.add('hidden');
                    }
                });
                container.appendChild(btn);
            });
        }

        function showSidebar(waypointId) {
            const wp = waypoints.find(w => w.id == waypointId);
            if (!wp) return;

            currentWaypoint = wp;
            currentStaff = null;

            document.getElementById('sidebar-name').innerText = wp.name;
            document.getElementById('sidebar-desc').innerText = wp.description || 'No description available.';
            if (wp.image_url) document.getElementById('sidebar-image').src = wp.image_url;

            // Show skeleton briefly
            const skel = document.getElementById('sidebar-skeleton');
            skel.classList.remove('hidden');
            setTimeout(() => skel.classList.add('hidden'), 350);

            refreshSidebarStaff();

            document.getElementById('experience-button').classList.toggle('hidden', !wp.pano_url);
            // Store current waypoint ID for the 3D button
            document.getElementById('enter-3d-btn')?.setAttribute('data-waypoint-id', wp.id);
            document.getElementById('selection-sidebar').style.transform = 'translateX(0)';
        }

        function refreshSidebarStaff() {
            if (!currentWaypoint) return;
            const wp = waypoints.find(w => w.id == currentWaypoint.id);
            if (!wp) return;

            const facultyList = document.getElementById('faculty-list');
            facultyList.innerHTML = '<h3 class="text-xs font-bold text-[#4A90E2] uppercase tracking-widest mb-3">Assigned Staff</h3>';

            const staffList = Array.isArray(wp.assigned_users) ? wp.assigned_users : (wp.assigned_user ? [wp.assigned_user] : []);
            if (!staffList.length) {
                facultyList.innerHTML += '<div class="flex flex-col items-center gap-2 py-8 text-center"><span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600">person_off</span><p class="text-sm text-slate-400 font-medium">No one\'s here right now.<br>Try another room or the front desk.</p></div>';
            } else {
                staffList
                    .slice()
                    .sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')))
                    .forEach((staff) => {
                        const row = document.createElement('button');
                        row.type = 'button';
                        row.className = 'w-full p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-between hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-left';
                        row.innerHTML = `
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-slate-400 shrink-0">
                                    <span class="material-symbols-outlined text-2xl">person</span>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 dark:text-slate-200 text-sm">${staff.name}</p>
                                    <p class="text-xs text-slate-500">${roleLabel(staff.role)}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 ${getStatusBg(staff.availability_status)} text-[10px] font-bold rounded-full uppercase shrink-0">${staff.availability_status || 'unknown'}</span>
                        `;
                        row.addEventListener('click', () => {
                            currentStaff = staff;
                            refreshStaffFocus();
                            openModal('modal-staff-focus');
                        });
                        facultyList.appendChild(row);
                    });
            }
        }

        function enterPanorama() {
            const btn = document.getElementById('enter-3d-btn');
            const id = btn ? btn.getAttribute('data-waypoint-id') : null;
            if (id) window.location.href = `/panorama/${id}`;
        }

        function hideSidebar() {
            document.getElementById('selection-sidebar').style.transform = 'translateX(100%)';
        }

        function getStatusColor(status) {
            if (status === 'available') return '#48BB78';
            if (status === 'busy')      return '#ED8936';
            return '#94A3B8';
        }
        function getStatusBg(status) {
            if (status === 'available') return 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400';
            if (status === 'busy')      return 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400';
            return 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400';
        }

        document.getElementById('btn-queue-window').addEventListener('click', () => {
            window.open(`/kiosk/queue`, '_blank');
        });

        document.getElementById('btn-consult').addEventListener('click', () => {
            if (!currentWaypoint || !currentStaff) return;
            if (currentStaff.availability_status !== 'available') return;
            openModal('modal-consult');
        });

        document.getElementById('btn-appointment').addEventListener('click', () => {
            if (!currentWaypoint || !currentStaff) return;
            openModal('modal-appointment');
        });

        // Staff Focus Modal Buttons
        document.getElementById('focus-btn-consult').addEventListener('click', () => {
            document.getElementById('modal-staff-focus').classList.add('hidden');
            document.getElementById('modal-staff-focus').classList.remove('flex');
            openModal('modal-consult');
        });

        document.getElementById('focus-btn-appointment').addEventListener('click', () => {
            document.getElementById('modal-staff-focus').classList.add('hidden');
            document.getElementById('modal-staff-focus').classList.remove('flex');
            openModal('modal-appointment');
        });

        document.querySelectorAll('.type-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                selectedApptType = btn.getAttribute('data-type');
                document.querySelectorAll('.type-btn').forEach((b) => b.classList.remove('ring-2', 'ring-primary/30'));
                btn.classList.add('ring-2', 'ring-primary/30');
                const gradeWrap = document.getElementById('appt-grade-wrap');
                if (selectedApptType === 'student') {
                    gradeWrap.classList.remove('hidden');
                } else {
                    gradeWrap.classList.add('hidden');
                }
            });
        });
        document.querySelector('.type-btn[data-type="student"]').click();

        document.getElementById('consult-submit').addEventListener('click', async () => {
            const error = document.getElementById('consult-error');
            error.classList.add('hidden');
            if (!currentWaypoint || !currentStaff) return;
            if (!selectedConsultPurpose) {
                error.textContent = 'Please select a purpose.';
                error.classList.remove('hidden');
                return;
            }
            const otherText = document.getElementById('consult-other').value.trim();
            if (selectedConsultPurpose.other && otherText === '') {
                error.textContent = 'Please specify details for the "Other" purpose.';
                error.classList.remove('hidden');
                return;
            }

            try {
                const res = await fetch('/api/kiosk/consultations', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        waypoint_id: currentWaypoint ? currentWaypoint.id : null,
                        assigned_user_id: currentStaff.id,
                        purpose_code: selectedConsultPurpose.code,
                        purpose_other_text: selectedConsultPurpose.other ? otherText : null,
                    }),
                });

                const data = await res.json();
                if (!res.ok) {
                error.querySelector('.msg').textContent = data && data.message ? data.message : 'Failed to create ticket.';
                error.classList.remove('hidden');
                error.classList.add('flex');
                return;
            }

            const displayNumber = (data && data.display_ticket_number)
                ? String(data.display_ticket_number)
                : formatTicketNumber(Number(data.ticket_number), currentStaff.role);
            const locationName = currentWaypoint ? currentWaypoint.name : 'Office';
            const meta = `${currentStaff.name} • ${locationName}`;
            document.getElementById('ticket-number').textContent = displayNumber;
            document.getElementById('ticket-meta').textContent = meta;
            closeAllModals();
            openModal('modal-ticket');
            fireConfetti();
            // Print only after the student presses "Done" on the ticket screen.
            pendingTicketPrint = { displayNumber, meta };
        } catch (e) {
            error.querySelector('.msg').textContent = 'Network error. Please try again.';
            error.classList.remove('hidden');
            error.classList.add('flex');
        }
    });

        document.getElementById('appt-submit').addEventListener('click', async () => {
            const error = document.getElementById('appt-error');
            error.classList.add('hidden');

            if (!currentWaypoint || !currentStaff) return;
            if (!selectedApptPurpose) {
                error.querySelector('.msg').textContent = 'Please select a purpose.';
                error.classList.remove('hidden');
                error.classList.add('flex');
                return;
            }

            const honorific = document.getElementById('appt-honorific').value;
            const name = document.getElementById('appt-name').value.trim();
            const email = document.getElementById('appt-email').value.trim();
            const grade = document.getElementById('appt-grade').value;
            const otherText = document.getElementById('appt-other').value.trim();

            if (name === '' || email === '') {
                error.querySelector('.msg').textContent = 'Please fill out name and email.';
                error.classList.remove('hidden');
                error.classList.add('flex');
                return;
            }
            if (selectedApptType === 'student' && !grade) {
                error.querySelector('.msg').textContent = 'Please select a grade level.';
                error.classList.remove('hidden');
                error.classList.add('flex');
                return;
            }
            if (selectedApptPurpose.other && otherText === '') {
                error.querySelector('.msg').textContent = 'Please specify details for the "Other" purpose.';
                error.classList.remove('hidden');
                error.classList.add('flex');
                return;
            }

            try {
                const res = await fetch('/api/kiosk/appointments', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        waypoint_id: currentWaypoint ? currentWaypoint.id : null,
                        target_user_id: currentStaff.id,
                        requester_type: selectedApptType,
                        requester_honorific: honorific,
                        requester_name: name,
                        grade_level: selectedApptType === 'student' ? grade : null,
                        requester_email: email,
                        purpose_code: selectedApptPurpose.code,
                        purpose_other_text: selectedApptPurpose.other ? otherText : null,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    error.querySelector('.msg').textContent = data && data.message ? data.message : 'Failed to submit appointment request.';
                    error.classList.remove('hidden');
                    error.classList.add('flex');
                    return;
                }

                closeAllModals();
                openModal('modal-appt-success');

                document.getElementById('appt-honorific').value = 'Mr.';
                document.getElementById('appt-name').value = '';
                document.getElementById('appt-email').value = '';
                document.getElementById('appt-grade').value = '';
                document.getElementById('appt-other').value = '';
            } catch (e) {
                error.querySelector('.msg').textContent = 'Network error. Please try again.';
                error.classList.remove('hidden');
                error.classList.add('flex');
            }
        });

        (function () {
            const osk = document.getElementById('osk');
            const keysHost = document.getElementById('osk-keys');
            const closeBtn = document.getElementById('osk-close');
            let target = null;
            let shift = false;

            const rows = [
                ['1','2','3','4','5','6','7','8','9','0','⌫'],
                ['q','w','e','r','t','y','u','i','o','p'],
                ['a','s','d','f','g','h','j','k','l'],
                ['⇪','z','x','c','v','b','n','m','@','.'],
                ['space','enter'],
            ];

            function isEditable(el) {
                if (!el) return false;
                if (!el.hasAttribute('data-osk')) return false;
                const tag = el.tagName ? el.tagName.toLowerCase() : '';
                if (tag === 'textarea') return !el.readOnly && !el.disabled;
                if (tag === 'input') {
                    const type = (el.getAttribute('type') || 'text').toLowerCase();
                    if (['text','email','search','tel','url','number'].includes(type)) {
                        return !el.readOnly && !el.disabled;
                    }
                }
                return false;
            }

            function open(el) {
                target = el;
                osk.classList.remove('hidden');
            }

            function close() {
                osk.classList.add('hidden');
                target = null;
                shift = false;
                render();
            }

            function insertText(el, text) {
                const start = el.selectionStart ?? el.value.length;
                const end = el.selectionEnd ?? el.value.length;
                const before = el.value.slice(0, start);
                const after = el.value.slice(end);
                el.value = before + text + after;
                const pos = start + text.length;
                el.setSelectionRange(pos, pos);
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }

            function backspace(el) {
                const start = el.selectionStart ?? el.value.length;
                const end = el.selectionEnd ?? el.value.length;
                if (start !== end) {
                    insertText(el, '');
                    return;
                }
                if (start === 0) return;
                const before = el.value.slice(0, start - 1);
                const after = el.value.slice(end);
                el.value = before + after;
                const pos = start - 1;
                el.setSelectionRange(pos, pos);
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }

            function keyLabel(k) {
                if (k === 'space') return 'Space';
                if (k === 'enter') return 'Enter';
                if (k === '⌫') return '⌫ Back';
                if (k === '⇪') return '⇪ Caps';
                return shift ? k.toUpperCase() : k;
            }

            function keyValue(k) {
                if (k === 'space') return ' ';
                if (k === 'enter') return '\n';
                if (k === '⌫' || k === '⇪') return null;
                return shift ? k.toUpperCase() : k;
            }

            function render() {
                keysHost.innerHTML = '';
                rows.forEach((row) => {
                    const rowEl = document.createElement('div');
                    rowEl.className = 'grid grid-flow-col auto-cols-fr gap-2';
                    row.forEach((k) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        const isAction = (k === 'space' || k === 'enter' || k === '⌫' || k === '⇪');
                        const wide = (k === 'space') ? 'col-span-6' : (k === 'enter') ? 'col-span-2' : '';
                        let keyClass = `h-16 rounded-2xl font-extrabold transition-all active:scale-95 active:bg-blue-600 active:text-white ${wide} `;
                        
                        if (k === 'enter') {
                            keyClass += 'bg-blue-600 text-white shadow-lg shadow-blue-200 dark:shadow-none border-b-4 border-blue-700';
                        } else if (k === '⇪') {
                            keyClass += shift 
                                ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-2 border-blue-200 dark:border-blue-800' 
                                : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border-2 border-slate-200 dark:border-slate-600';
                        } else if (k === '⌫') {
                            keyClass += 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border-2 border-slate-200 dark:border-slate-600';
                        } else if (isAction) {
                            keyClass += 'bg-slate-50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-600';
                        } else {
                            keyClass += 'bg-white dark:bg-slate-800 text-slate-800 dark:text-white border border-slate-200 dark:border-slate-700 shadow-sm';
                        }
                        
                        btn.className = keyClass;
                        btn.textContent = keyLabel(k);
                        btn.addEventListener('click', () => {
                            if (!target || !isEditable(target)) return;
                            target.focus();
                            if (k === '⇪') {
                                shift = !shift;
                                render();
                                return;
                            }
                            if (k === '⌫') {
                                backspace(target);
                                return;
                            }
                            if (k === 'enter') {
                                if (target && (target.id === 'feedback-suggestions' || target.name === 'suggestions' || target.closest('#modal-feedback'))) {
                                    close();
                                    target.blur();
                                } else if (target.tagName.toLowerCase() === 'textarea') {
                                    insertText(target, '\n');
                                } else {
                                    close();
                                }
                                return;
                            }
                            if (k === 'space') {
                                insertText(target, ' ');
                                return;
                            }
                            const v = keyValue(k);
                            if (v) insertText(target, v);
                        });
                        rowEl.appendChild(btn);
                    });
                    keysHost.appendChild(rowEl);
                });
            }

            render();

            document.addEventListener('focusin', (e) => {
                const el = e.target;
                if (isEditable(el)) {
                    open(el);
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && e.target && (e.target.id === 'feedback-suggestions' || e.target.closest('#modal-feedback'))) {
                    e.preventDefault();
                    close();
                    e.target.blur();
                }
            });

            document.addEventListener('mousedown', (e) => {
                if (osk.classList.contains('hidden')) return;
                const t = e.target;
                if (t && (osk.contains(t) || (target && target === t))) return;
                if (target && isEditable(target) && document.activeElement === target) return;
                close();
            });

            document.addEventListener('touchstart', (e) => {
                if (osk.classList.contains('hidden')) return;
                const t = e.target;
                if (t && (osk.contains(t) || isEditable(t))) return;
                close();
            }, { passive: true });

            closeBtn.addEventListener('click', close);
        })();

        const container = document.getElementById('waypoints-container');
        waypoints.forEach((wp, index) => {
            const btn = document.createElement('button');
            btn.className = 'absolute group flex flex-col items-center pin-pop pointer-events-auto';
            btn.style.left = wp.coordinates_x + '%';
            btn.style.top  = wp.coordinates_y + '%';
            btn.style.transform = 'translate(-50%, -100%)';
            btn.style.animationDelay = (index * 60) + 'ms';
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                // bounce animation
                btn.classList.remove('pin-bounce');
                void btn.offsetWidth;
                btn.classList.add('pin-bounce');
                showSidebar(wp.id);
            });
            btn.innerHTML = `
                <div class="relative flex flex-col items-center pointer-events-none">
                    <div class="absolute bottom-full mb-1 px-3 py-1 bg-white text-[#4A90E2] text-[10px] font-black rounded-full shadow-xl whitespace-nowrap opacity-0 group-hover:opacity-100 group-hover:-translate-y-1 transition-all duration-300 z-10 border border-blue-100">
                        ${wp.name}
                    </div>
                    <span class="pin-icon material-symbols-outlined text-[#4A90E2] text-4xl group-hover:text-blue-600 transition-all drop-shadow-2xl" style="font-variation-settings:'FILL' 1">location_on</span>
                </div>
            `;
            container.appendChild(btn);
        });

        // ── OSK preview bar update ──
        document.addEventListener('input', (e) => {
            const preview = document.getElementById('osk-preview');
            if (preview && e.target && e.target.hasAttribute('data-osk')) {
                preview.textContent = e.target.value || '';
            }
        });
        // ── Polling Logic for Real-time Status ──
        async function syncKioskData() {
            try {
                const res = await fetch('/api/kiosk/status');
                if (!res.ok) return;
                const data = await res.json();

                // Update local variables
                waypoints = data.waypoints;
                staffMembers = data.staffMembers;

                // Refresh UI components if visible
                if (currentWaypoint && document.getElementById('selection-sidebar').style.transform === 'translateX(0px)') {
                    refreshSidebarStaff();
                }

                if (currentStaff && !document.getElementById('modal-staff-focus').classList.contains('hidden')) {
                    refreshStaffFocus();
                }

                if (!document.getElementById('modal-staff-directory').classList.contains('hidden')) {
                    openStaffDirectory();
                }
            } catch (e) {
                // Silently fail
            }
        }

        // Start polling every 5 seconds
        setInterval(syncKioskData, 5000);
    </script>
    </div>
@endsection
