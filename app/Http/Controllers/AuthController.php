<?php

namespace App\Http\Controllers;

use App\Mail\AccountRecoveryMail;
use App\Mail\SendOTP;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\ActivityLog;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Show the initial login form.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Process the first step of login (email/password) and send OTP via Gmail SMTP.
     */
    public function processLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user && Hash::check($request->password, $user->password)) {
            // RESTRICTION: Only Admin role can login to the website/portal
            if ($user->role !== 'admin') {
                return back()->withErrors(['email' => 'Access Denied: Only administrators can access this portal.']);
            }

            // Generate 6-digit OTP
            $otp = rand(111111, 999999);

            // Save OTP to DB with expiry (10 min)
            $user->update([
                'otp_code' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            // Store user ID temporarily in session for OTP step
            session(['otp_user_id' => $user->id]);

            // Send OTP email via Gmail SMTP
            try {
                Mail::to($user->email)->send(new SendOTP($otp));
                $statusMessage = "An OTP verification code has been sent to {$user->email}.";
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('OTP Email Sending Error: ' . $e->getMessage());
                return back()->withErrors(['email' => 'Unable to send verification code: ' . $e->getMessage()]);
            }

            return redirect()->route('auth.otp')->with('status', $statusMessage);
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    /**
     * Handle Forgot Password request from the Web Admin Login screen.
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No account found with that email address.']);
        }

        $newPassword = 'MIT' . random_int(10000, 99999);
        $user->password = Hash::make($newPassword);
        $user->save();

        $prefix = $user->isAdmin() ? "Admin User" : "Staff";
        ActivityLog::record(
            'forgot_password',
            "{$prefix} Requested Account & Password Recovery via Email - {$user->name}",
            ['email' => $user->email, 'channel' => 'web'],
            $user
        );

        try {
            Mail::to($user->email)->send(new AccountRecoveryMail($user, $newPassword));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Web Forgot Password Email Error: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Failed to send recovery email: ' . $e->getMessage()]);
        }

        return back()->with('status', "All account information and your new password have been sent to {$user->email}.");
    }

    /**
     * Show the OTP entry form.
     */
    public function showOTP()
    {
        if (!session()->has('otp_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.otp');
    }

    /**
     * Verify the entered OTP.
     */
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric',
        ]);

        $user = User::findOrFail(session('otp_user_id'));

        // Additional guard: ensure role is still admin
        if ($user->role !== 'admin') {
            session()->forget('otp_user_id');
            return redirect()->route('login')->withErrors(['email' => 'Access Denied: Administrative privileges required.']);
        }

        // Using strict comparison for security. Input from request is cast to string.
        if ((string)$user->otp_code === (string)$request->otp && $user->otp_expires_at->gt(Carbon::now())) {
            // Clear OTP data
            $user->update([
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);

            // Log in user
            Auth::login($user);
            session()->forget('otp_user_id');

            $time = Carbon::now()->format('h:i A');
            ActivityLog::record('login', "Admin User Logged In at {$time} - {$user->name}", ['channel' => 'web'], $user);

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['otp' => 'Invalid or expired OTP code.']);
    }

    /**
     * Resend a new OTP to the user in session.
     */
    public function resendOTP(Request $request)
    {
        if (!session()->has('otp_user_id')) {
            return redirect()->route('login');
        }

        $user = User::findOrFail(session('otp_user_id'));
        
        // Generate NEW 6-digit OTP
        $otp = rand(111111, 999999);
        
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        try {
            Mail::to($user->email)->send(new SendOTP($otp));
            $statusMessage = 'A new verification code has been sent!';
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP Resend Error: ' . $e->getMessage());
            return back()->withErrors(['otp' => 'Unable to send verification code. Please check your mail settings.']);
        }

        return back()->with('status', $statusMessage);
    }

    /**
     * Logout the administrator.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $time = Carbon::now()->format('h:i A');
            $prefix = $user->isAdmin() ? "Admin User" : "Staff";
            ActivityLog::record('logout', "{$prefix} Logged Out at {$time} - {$user->name}", ['channel' => 'web'], $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
