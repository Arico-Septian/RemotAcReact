<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'name',
        'device_id',
        'floor',
        'device_status',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    public function acUnits()
    {
        return $this->hasMany(AcUnit::class);
    }

}
