<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('bems_clients')->cascadeOnDelete();
            $table->enum('staff_role', ['maintenance', 'operator', 'viewer']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Satu user hanya bisa jadi 1 staff per client
            $table->unique(['user_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
