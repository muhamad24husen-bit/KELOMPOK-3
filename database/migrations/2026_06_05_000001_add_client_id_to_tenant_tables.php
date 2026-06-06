<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Denormalisasi client_id ke tabel aset agar TenantScope
     * bisa langsung filter WHERE client_id = X tanpa JOIN.
     */
    public function up(): void
    {
        // ── 1. nodes ───────────────────────────────────────────
        Schema::table('nodes', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('room_id')
                  ->constrained('bems_clients')->cascadeOnDelete();
        });
        // Backfill: ambil client_id dari room parent
        DB::statement('
            UPDATE nodes
            SET client_id = (
                SELECT rooms.client_id FROM rooms WHERE rooms.id = nodes.room_id
            )
            WHERE client_id IS NULL
        ');

        // ── 2. sensors ─────────────────────────────────────────
        Schema::table('sensors', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('room_id')
                  ->constrained('bems_clients')->cascadeOnDelete();
        });
        DB::statement('
            UPDATE sensors
            SET client_id = (
                SELECT rooms.client_id FROM rooms WHERE rooms.id = sensors.room_id
            )
            WHERE client_id IS NULL
        ');

        // ── 3. sensor_logs ─────────────────────────────────────
        Schema::table('sensor_logs', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('node_id')
                  ->constrained('bems_clients')->cascadeOnDelete();
        });
        DB::statement('
            UPDATE sensor_logs
            SET client_id = (
                SELECT nodes.client_id FROM nodes WHERE nodes.id = sensor_logs.node_id
            )
            WHERE client_id IS NULL
        ');

        // ── 4. operation_requests ──────────────────────────────
        Schema::table('operation_requests', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('node_id')
                  ->constrained('bems_clients')->cascadeOnDelete();
        });
        DB::statement('
            UPDATE operation_requests
            SET client_id = (
                SELECT nodes.client_id FROM nodes WHERE nodes.id = operation_requests.node_id
            )
            WHERE client_id IS NULL
        ');

        // ── 5. activities ──────────────────────────────────────
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('node_id')
                  ->constrained('bems_clients')->cascadeOnDelete();
        });
        DB::statement('
            UPDATE activities
            SET client_id = (
                SELECT nodes.client_id FROM nodes WHERE nodes.id = activities.node_id
            )
            WHERE activities.node_id IS NOT NULL AND client_id IS NULL
        ');
    }

    public function down(): void
    {
        $tables = ['nodes', 'sensors', 'sensor_logs', 'operation_requests', 'activities'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('client_id');
            });
        }
    }
};
