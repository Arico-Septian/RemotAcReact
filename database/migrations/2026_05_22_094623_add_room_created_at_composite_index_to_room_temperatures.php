<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_temperatures', function (Blueprint $table) {
            $table->index(['room', 'created_at'], 'room_temperatures_room_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('room_temperatures', function (Blueprint $table) {
            $table->dropIndex('room_temperatures_room_created_at_index');
        });
    }
};
