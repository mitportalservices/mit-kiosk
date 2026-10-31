<?php

namespace App\Http\Controllers;

use App\Mail\AccountRecoveryMail;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class MobileAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        if (!in_array($user->role, ['faculty', 'principal', 'guidance_counselor', 'admin'], true)) {
            return response()->json(['message' => 'This account is not allowed to access this app.'], 403);
        }

        $plain = bin2hex(random_bytes(32));
        $user->api_token_hash = hash('sha256', $plain);
        $user->api_token_expires_at = now()->addDays(30);
        $user->save();

        $time = Carbon::now()->format('h:i A');
        $prefix = $user->isAdmin() ? "Admin User" : "Staff";
        ActivityLog::record('login', "{$prefix} Logged In at {$time} - {$user->name}", ['client' => 'mobile'], $user);

        return response()->json([
            'token' => $plain,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'availability_status' => $user->availability_status,
                'time_availability' => $user->time_availability,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'availability_status' => $user->availability_status,
            'time_availability' => $user->time_availability,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $time = Carbon::now()->format('h:i A');
            $prefix = $user->isAdmin() ? "Admin User" : "Staff";
            ActivityLog::record('logout', "{$prefix} Logged Out at {$time} - {$user->name}", ['client' => 'mobile'], $user);

            $user->api_token_hash = null;
            $user->api_token_expires_at = null;
            $user->save();
        }

        return response()->json(['ok' => true]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with that email address.',
            ], 422);
        }

        $newPassword = 'MIT' . random_int(10000, 99999);
        $user->password = Hash::make($newPassword);
        $user->save();

        $prefix = $user->isAdmin() ? "Admin User" : "Staff";
        ActivityLog::record(
            'forgot_password',
            "{$prefix} Requested Account & Password Recovery via Email - {$user->name}",
            ['email' => $user->email],
            $user
        );

        try {
            Mail::to($user->email)->send(new AccountRecoveryMail($user, $newPassword));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to send recovery email: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => "All account information and your new password have been sent to {$user->email}.",
        ]);
    }
}
