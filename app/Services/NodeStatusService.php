<?php

namespace App\Services;

use App\Models\BEMS\Room;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NodeStatusService
{
    /**
     * Get the latest status of a sensor from cache.
     */
    public static function get(int $sensorId): ?array
    {
        try {
            return Cache::get("node:status:{$sensorId}");
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Store sensor status in cache with TTL.
     */
    public static function set(int $sensorId, array $data, int $ttl = 300): void
    {
        try {
            Cache::put("node:status:{$sensorId}", $data, $ttl);
            Log::debug("NodeStatusService::set sensor #{$sensorId}", $data);
        } catch (\Throwable $e) {
            Log::warning("NodeStatusService cache write failed: {$e->getMessage()}");
        }
    }

    /**
     * Get aggregated stats for a client from cache (with DB fallback).
     */
    public static function getClientStats(int $clientId): array
    {
        return Cache::remember("client:stats:{$clientId}", 30, function () use ($clientId) {
            return static::computeClientStats($clientId);
        });
    }

    /**
     * Compute client stats from database.
     */
    private static function computeClientStats(int $clientId): array
    {
        $rooms = Room::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->with('nodes')
            ->get();

        return [
            'total_rooms'   => $rooms->count(),
            'online_nodes'  => $rooms->sum(fn ($r) => $r->nodes->where('status', 'online')->count()),
            'offline_nodes' => $rooms->sum(fn ($r) => $r->nodes->where('status', 'offline')->count()),
            'warning_nodes' => $rooms->sum(fn ($r) => $r->nodes->where('status', 'warning')->count()),
        ];
    }

    /**
     * Publish a command to hardware via MQTT.
     */
    public static function publishCommand(string $mqttSubTopic, string $action, array $payload = []): void
    {
        try {
            $mqttService = app(\App\Services\MqttService::class);
            $mqttService->publishCommand($mqttSubTopic, [
                'action'  => $action,
                'payload' => $payload,
                'ts'      => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            logger()->warning('MQTT command failed: ' . $e->getMessage(), [
                'topic'  => $mqttSubTopic,
                'action' => $action,
            ]);
        }
    }
}
