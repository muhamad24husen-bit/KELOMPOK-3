<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_logs', function (Blueprint $table) {
            $table->id(); // BigInt auto-increment for high volume
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->string('metric'); // 'temperature', 'humidity', 'co2', dll
            $table->decimal('value', 8, 2);
            $table->timestamp('recorded_at')->useCurrent();

            // Index kritis untuk query grafik histori
            $table->index(['node_id', 'metric', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_logs');
    }
};
