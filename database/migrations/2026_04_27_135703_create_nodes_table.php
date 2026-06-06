<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('device_id')->unique(); // MAC Address ESP32
            $table->string('name')->nullable();
            $table->enum('status', ['online', 'offline', 'warning'])->default('offline');
            $table->timestamp('last_heartbeat')->nullable();
            $table->json('meta')->nullable(); // RSSI, firmware version, dll
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
