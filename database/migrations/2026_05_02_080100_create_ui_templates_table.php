<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // e.g. "Server Room Pro"
            $table->string('icon')->nullable();      // Material icon name
            $table->text('description')->nullable();
            $table->json('schema');                   // JSON array of input definitions
            $table->json('default_mapping')->nullable(); // Default I/O mapping values
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_templates');
    }
};
