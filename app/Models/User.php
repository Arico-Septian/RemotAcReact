<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'avatar',
        'password',
        'role',
        'is_online',
        'last_activity',
        'last_login_at',
        'last_logout_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'last_login_at' => 'datetime',
        'last_logout_at' => 'datetime',
        'is_online' => 'boolean',
    ];

    protected $appends = ['isOnline', 'status_text', 'avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/'.$this->avatar) : null;
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isOperator()
    {
        return $this->role === 'operator';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function getIsOnlineAttribute()
    {
        return $this->last_activity && $this->last_activity->gte(now()->subMinutes(2));
    }

    public function getStatusTextAttribute()
    {
        if ($this->isOnline) {
            return 'Online';
        }

        if ($this->last_activity) {
            return $this->last_activity->diffForHumans();
        }

        return 'Offline';
    }

    public function getStatusColorAttribute()
    {
        return $this->isOnline ? 'text-green-400' : 'text-gray-400';
    }

    public function getStatusDotAttribute()
    {
        return $this->isOnline ? 'bg-green-400' : 'bg-gray-500';
    }
}
