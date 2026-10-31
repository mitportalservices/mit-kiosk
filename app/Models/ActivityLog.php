<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_role',
        'user_name',
        'action',
        'description',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a new activity log entry.
     *
     * @param string $action
     * @param string $description
     * @param array|null $details
     * @param User|null $user
     * @return static
     */
    public static function record(string $action, string $description, ?array $details = null, ?User $user = null): static
    {
        $actor = $user ?? auth()->user();

        return static::create([
            'user_id' => $actor?->id,
            'user_role' => $actor?->role ?? 'system',
            'user_name' => $actor ? $actor->name : 'System',
            'action' => $action,
            'description' => $description,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
