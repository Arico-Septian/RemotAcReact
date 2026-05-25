<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_units', function (Blueprint $table) {
            $table->unique(['room_id', 'ac_number'], 'ac_units_room_id_ac_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ac_units', function (Blueprint $table) {
            $table->dropUnique('ac_units_room_id_ac_number_unique');
        });
    }
};
