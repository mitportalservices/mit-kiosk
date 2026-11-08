@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-slate-50">
        <div class="w-full max-w-md p-8 bg-white rounded-3xl shadow-xl border border-slate-100">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4 text-white shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-4xl">admin_panel_settings</span>
                </div>
                <h1 class="text-3xl font-extrabold font-manrope text-slate-800 mb-1">MIT Maps Admin</h1>
                <p class="text-slate-500">Sign in to manage your campus sanctuary</p>
            </div>

            <div class="mb-6">
                <button type="button" onclick="const enabled = window.toggleOSK(); this.querySelector('.status').textContent = enabled ? 'ON' : 'OFF'; this.classList.toggle('bg-blue-100', enabled); this.classList.toggle('text-blue-700', enabled); this.classList.toggle('bg-slate-100', !enabled); this.classList.toggle('text-slate-500', !enabled);" 
                    class="w-full py-3 px-4 bg-slate-100 text-slate-500 rounded-2xl font-bold text-xs flex items-center justify-center gap-2 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-lg">keyboard</span>
                    VIRTUAL KEYBOARD: <span class="status">OFF</span>
                </button>
            </div>

            @if (session('status'))
                <div class="p-4 mb-6 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-2xl font-bold text-center leading-relaxed" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-slate-600 mb-2">Administrator Email</label>
                    <input type="email" id="login-email" name="email" value="{{ old('email') }}" data-osk="1" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-slate-800 focus:ring-2 focus:ring-primary/20 transition-all font-medium" placeholder="admin@mit-maps.com">
                    <p id="forgot-inline-error" class="mt-2 text-xs text-red-500 font-bold hidden"></p>
                    @error('email') <p class="mt-2 text-xs text-red-500 font-bold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-bold text-slate-600">Master Password</label>
                        <button type="button" id="forgot-btn" onclick="submitForgotPassword()" class="text-xs font-extrabold text-primary hover:underline">
                            Forgot Password?
                        </button>
                    </div>
                    <input type="password" name="password" data-osk="1" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-slate-800 focus:ring-2 focus:ring-primary/20 transition-all font-medium" placeholder="••••••••">
                    @error('password') <p class="mt-2 text-xs text-red-500 font-bold">{{ $message }}</p> @enderror
                </div>

                @error('otp')
                    <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-2xl font-bold text-center" role="alert">
                        {{ $message }}
                    </div>
                @enderror

                <button type="submit" class="w-full py-5 bg-gradient-to-r from-primary to-blue-600 text-white rounded-2xl font-bold shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all">
                    Send OTP Verification
                </button>
            </form>

            <!-- Hidden form that directly submits the email already typed in Administrator Email -->
            <form id="forgot-direct-form" action="{{ route('auth.forgot_password') }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" id="forgot-email" name="email">
            </form>

            <script>
                function submitForgotPassword() {
                    const loginEmail = document.getElementById('login-email');
                    const errEl = document.getElementById('forgot-inline-error');
                    const email = (loginEmail ? loginEmail.value : '').trim();

                    if (!email || !email.includes('@')) {
                        if (errEl) {
                            errEl.textContent = 'Please type your Administrator Email in the box above first, then click Forgot Password.';
                            errEl.classList.remove('hidden');
                        }
                        if (loginEmail) loginEmail.focus();
                        return;
                    }

                    if (errEl) errEl.classList.add('hidden');
                    const btn = document.getElementById('forgot-btn');
                    if (btn) {
                        btn.disabled = true;
                        btn.textContent = 'Sending to your email…';
                    }
                    document.getElementById('forgot-email').value = email;
                    document.getElementById('forgot-direct-form').submit();
                }
            </script>

            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col items-center gap-3 text-center">
                <a href="{{ route('download.apk') }}" class="w-full py-3 px-4 bg-blue-50 hover:bg-blue-100 text-primary rounded-2xl font-bold text-xs flex items-center justify-center gap-2 transition-all active:scale-95">
                    <span class="material-symbols-outlined text-base">android</span>
                    Download Staff &amp; Admin Android App (.APK)
                </a>
                <a href="{{ route('visitor.kiosk') }}" class="text-sm font-bold text-slate-400 hover:text-primary transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Return to Visitor Guide
                </a>
            </div>
        </div>
    </div>

    @include('partials.osk')
@endsection
