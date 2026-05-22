<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE ac_statuses MODIFY mode ENUM('COOL','HEAT','DRY','FAN','AUTO') NOT NULL DEFAULT 'COOL'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE ac_statuses MODIFY mode ENUM('COOL','HEAT','DRY','FAN','AUTO') NOT NULL DEFAULT 'AUTO'");
    }
};
