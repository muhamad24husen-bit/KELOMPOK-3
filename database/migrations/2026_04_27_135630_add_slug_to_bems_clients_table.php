<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bems_clients', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // Auto-generate slug untuk data yang sudah ada
        foreach (\App\Models\BEMS\Client::all() as $client) {
            $client->update(['slug' => Str::slug($client->code ?? $client->name)]);
        }
    }

    public function down(): void
    {
        Schema::table('bems_clients', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
