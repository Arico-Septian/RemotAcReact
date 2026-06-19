<?php

namespace App\Models;

use App\Events\AcStatusUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

            // Broadcasting opsional: jangan gagalkan request kalau Reverb mati.
            try {
                event(new AcStatusUpdated($status));
            } catch (\Throwable $e) {
                Log::warning('Broadcast AcStatusUpdated gagal: '.$e->getMessage());
            }
        });
    }
}
