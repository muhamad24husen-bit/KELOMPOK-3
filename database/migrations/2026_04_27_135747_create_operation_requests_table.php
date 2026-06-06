<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('action'); // 'ac_on', 'ac_off', 'light_on', 'light_off', dll
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'executed'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_requests');
    }
};
