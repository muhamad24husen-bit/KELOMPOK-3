<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('bems_clients')->cascadeOnDelete();
            $table->string('name');
            $table->string('floor')->nullable();
            $table->integer('total_nodes')->default(0);
            $table->string('status')->default('OPERATIONAL'); // OPERATIONAL, MAINTENANCE, CRITICAL
            $table->string('icon')->nullable()->default('door_open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
