<?php

namespace App\Models\BEMS;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Sensor extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'room_id',
        'client_id',
        'node_id',
        'mac_address',
        'type',
        'measurement_type',
        'unit',
        'visualization_type',
        'is_enabled',
        'floor',
        'provision_status',
        'mqtt_pub_topic',
        'mqtt_sub_topic',
        'provisioned_at',
    ];

    protected $casts = [
        'is_enabled'     => 'boolean',
        'provisioned_at' => 'datetime',
    ];

    protected function resolveTenantClientId(): ?int
    {
        return $this->room?->client_id;
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    // ── MQTT Topic Generation (Zero-Config Provisioning) ────

    /**
     * Generate publish topic from hierarchy.
     * Format: {client_slug}/{room_slug}/{mac}/telemetry
     */
    public function generatePubTopic(): string
    {
        $room   = $this->room;
        $client = $room->client;

        $clientSlug = $client->slug ?? Str::slug($client->code ?? 'client');
        $roomSlug   = $room->slug ?? Str::slug($room->name);
        $mac        = strtolower(str_replace([':', '-'], '', $this->mac_address));

        return "{$clientSlug}/{$roomSlug}/{$mac}/telemetry";
    }

    /**
     * Generate subscribe topic (for commands to ESP32).
     */
    public function generateSubTopic(): string
    {
        return str_replace('/telemetry', '/command', $this->generatePubTopic());
    }

    /**
     * Generate and save MQTT topics to DB.
     * Called after sensor creation or when room changes.
     */
    public function refreshMqttTopics(): void
    {
        $this->update([
            'mqtt_pub_topic'   => $this->generatePubTopic(),
            'mqtt_sub_topic'   => $this->generateSubTopic(),
            'provision_status' => 'waiting_provision',
        ]);
    }
}
