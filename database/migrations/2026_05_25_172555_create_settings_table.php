<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type')->default('integer');
            $table->string('label');
            $table->string('unit')->nullable();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['key' => 'temp_retention_days',         'value' => '7',   'type' => 'integer', 'label' => 'Simpan data suhu',        'unit' => 'hari'],
            ['key' => 'notification_retention_days', 'value' => '30',  'type' => 'integer', 'label' => 'Simpan notifikasi',        'unit' => 'hari'],
            ['key' => 'log_retention_days',          'value' => '90',  'type' => 'integer', 'label' => 'Simpan activity log',      'unit' => 'hari'],
            ['key' => 'fuzzy_temp_cold',             'value' => '22',  'type' => 'integer', 'label' => 'Batas suhu Dingin (maks)', 'unit' => '°C'],
            ['key' => 'fuzzy_temp_hot',              'value' => '30',  'type' => 'integer', 'label' => 'Batas suhu Panas (min)',   'unit' => '°C'],
            ['key' => 'sensor_stale_seconds',        'value' => '120', 'type' => 'integer', 'label' => 'Sensor dianggap offline',  'unit' => 'detik'],
            ['key' => 'device_offline_seconds',      'value' => '90',  'type' => 'integer', 'label' => 'Device dianggap offline',  'unit' => 'detik'],
        ];

        foreach ($defaults as $row) {
            DB::table('settings')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
