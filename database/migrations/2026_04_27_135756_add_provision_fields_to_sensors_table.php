<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->foreignId('node_id')->nullable()->constrained()->nullOnDelete()->after('room_id');
            $table->enum('provision_status', [
                'pending',           // Baru didaftarkan di dashboard
                'waiting_provision', // MAC sudah diisi, menunggu ESP32 kontak
                'provisioned',       // ESP32 sudah terima config, aktif
                'reprovisioning',    // Sedang dikirim config baru (pindah ruangan)
            ])->default('pending')->after('is_enabled');
            $table->string('mqtt_pub_topic')->nullable()->after('provision_status');
            $table->string('mqtt_sub_topic')->nullable()->after('mqtt_pub_topic');
            $table->timestamp('provisioned_at')->nullable()->after('mqtt_sub_topic');
        });
    }

    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropColumn(['node_id', 'provision_status', 'mqtt_pub_topic', 'mqtt_sub_topic', 'provisioned_at']);
        });
    }
};
