<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->index(['client_id', 'slug']);
        });

        // Auto-generate slug untuk data yang sudah ada
        foreach (\App\Models\BEMS\Room::withoutGlobalScopes()->get() as $room) {
            $room->update(['slug' => Str::slug($room->name)]);
        }
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
