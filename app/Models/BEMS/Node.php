<?php

namespace App\Models\BEMS;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Node extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'room_id',
        'client_id',
        'device_id',
        'name',
        'status',
        'last_heartbeat',
        'meta',
        'ui_template_id',
        'io_mapping',
    ];

    protected $casts = [
        'meta'           => 'array',
        'io_mapping'     => 'array',
        'last_heartbeat' => 'datetime',
    ];

    protected function resolveTenantClientId(): ?int
    {
        return $this->room?->client_id;
    }

    // ── Relationships ──────────────────────────────────────────

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function uiTemplate()
    {
        return $this->belongsTo(UiTemplate::class);
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function logs()
    {
        return $this->hasMany(SensorLog::class);
    }

    // ── MQTT Topic Generation ──────────────────────────────────

    /**
     * Build the MQTT telemetry topic from the hierarchy.
     * e.g. client-slug/room-slug/a1b2c3d4e5/telemetry
     */
    public function getMqttTelemetryTopicAttribute(): string
    {
        $room   = $this->room;
        $client = $room?->client;

        $clientSlug = $client?->slug ?? 'unknown';
        $roomSlug   = $room?->slug   ?? 'unknown';
        $mac        = strtolower(str_replace([':', '-'], '', $this->device_id));

        return "{$clientSlug}/{$roomSlug}/{$mac}/telemetry";
    }

    /**
     * Build the MQTT command topic.
     */
    public function getMqttCommandTopicAttribute(): string
    {
        return str_replace('/telemetry', '/command', $this->mqtt_telemetry_topic);
    }

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Parse raw MQTT JSON using io_mapping to produce labelled data.
     */
    public function parsePayload(array $raw): array
    {
        $mapping = $this->io_mapping ?? [];
        $parsed  = [];

        foreach ($mapping as $label => $key) {
            if (str_ends_with($label, '_key') && isset($raw[$key])) {
                $cleanLabel        = str_replace('_key', '', $label);
                $parsed[$cleanLabel] = $raw[$key];
            }
        }

        // Check danger threshold
        if (isset($mapping['danger_temp'], $parsed['temp'])) {
            $parsed['is_danger'] = $parsed['temp'] > $mapping['danger_temp'];
        }

        return $parsed;
    }

    /**
     * Check if node has been seen within the last 2 minutes.
     */
    public function isOnline(): bool
    {
        return $this->last_heartbeat?->gt(now()->subMinutes(2)) ?? false;
    }
}
