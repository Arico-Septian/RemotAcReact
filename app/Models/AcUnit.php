<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcUnit extends Model
{

    protected $fillable = [
        'room_id',
        'ac_number',
        'name',
        'brand',
        'location',
        'ip_ir',
        'timer_on',
        'timer_off'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function status()
    {
        return $this->hasOne(AcStatus::class);
    }
}
