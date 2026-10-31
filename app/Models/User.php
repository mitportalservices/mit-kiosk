<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'title',
        'email',
        'password',
        'role',
        'availability_status',
        'time_availability',
        'otp_code',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token_hash',
        'otp_code',
    ];

    /** Check if account is admin */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /** Check if account is faculty */
    public function isFaculty()
    {
        return $this->role === 'faculty';
    }

    public function isPrincipal()
    {
        return $this->role === 'principal';
    }

    public function isGuidanceCounselor()
    {
        return $this->role === 'guidance_counselor';
    }

    public function waypoints()
    {
        return $this->belongsToMany(Waypoint::class, 'waypoint_user')->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',
            'api_token_expires_at' => 'datetime',
        ];
    }
}
