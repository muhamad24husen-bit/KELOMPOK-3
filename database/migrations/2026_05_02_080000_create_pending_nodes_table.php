<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address')->unique();
            $table->string('chip_type')->nullable();
            $table->string('firmware_ver')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_nodes');
    }
};
