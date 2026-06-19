<?php

namespace App\Models;

use App\Events\UserLogCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class UserLog extends Model
{
    protected $fillable = [
        'user_id',
        'room',
        'ac',
        'activity'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class)->withDefault(function ($user, $log) {
            $user->name = $log->user_id === null ? 'System' : 'Deleted User';
        });
    }

    protected static function booted(): void
    {
        static::created(function (UserLog $log) {
            // Broadcasting opsional: jangan gagalkan request kalau Reverb mati.
            try {
                event(new UserLogCreated($log));
            } catch (\Throwable $e) {
                Log::warning('Broadcast UserLogCreated gagal: '.$e->getMessage());
            }
        });
    }
}
