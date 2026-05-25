<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRead extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    // Composite PK — Eloquent tidak mendukung composite PK secara native.
    // Gunakan hanya updateOrInsert/insertOrIgnore (bukan save/find/create).
    protected $primaryKey = null;

    protected $fillable = ['notification_id', 'user_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
