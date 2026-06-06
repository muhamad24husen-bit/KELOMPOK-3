<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->foreignId('ui_template_id')
                  ->nullable()
                  ->after('meta')
                  ->constrained('ui_templates')
                  ->nullOnDelete();

            $table->json('io_mapping')->nullable()->after('ui_template_id');
            // e.g. {"temp_key": "t", "hum_key": "h", "smoke_key": "s", "danger_temp": 30.0}
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropForeign(['ui_template_id']);
            $table->dropColumn(['ui_template_id', 'io_mapping']);
        });
    }
};
