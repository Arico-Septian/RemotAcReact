<?php

namespace App\Models;

use App\Events\AcStatusUpdated;
use Illuminate\Database\Eloquent\Model;

class AcStatus extends Model
{
    protected $fillable = [
        'ac_unit_id',
        'power',
        'set_temperature',
        'mode',
        'fan_speed',
        'swing',
    ];

    public function acUnit()
    {
        return $this->belongsTo(\App\Models\AcUnit::class);
    }

    protected static function booted(): void
    {
        static::saved(function (AcStatus $status) {
            if (! $status->wasRecentlyCreated && ! $status->wasChanged([
                'power', 'mode', 'set_temperature', 'fan_speed', 'swing',
            ])) {
                return;
            }

            event(new AcStatusUpdated($status));
        });
    }
}
