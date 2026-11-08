Hello {{ $user->name }},

Here are your requested account details for the MIT Campus Staff & Admin Portal:

Full Name: {{ $user->name }}
Email Address: {{ $user->email }}
Account Role: {{ ucwords(str_replace('_', ' ', $user->role)) }}
Current Status: {{ ucfirst($user->availability_status ?? 'available') }}
Time Availability: {{ $user->time_availability ?: '8:00 AM - 5:00 PM' }}
Temporary Sign-In Code / Password: {{ $newPassword }}

You can sign in to the MIT Campus Portal using your email address and the temporary password above. Once signed in, you can update your password anytime from your Profile screen.

Thank you,
MIT Campus Portal
