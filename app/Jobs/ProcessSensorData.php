<?php

namespace App\Jobs;

use App\Models\BEMS\Activity;
use App\Models\BEMS\Sensor;
use App\Models\BEMS\SensorLog;
use App\Services\NodeStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSensorData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $macAddress,
        private array  $data
    ) {}

    public function handle(): void
    {
        $sensor = Sensor::where('mac_address', $this->macAddress)->first();

        if (!$sensor) {
            Log::warning("ProcessSensorData: Sensor not found for MAC {$this->macAddress}");
            return;
        }

        // ── Normalize keys ──────────────────────────────────────
        // Support both short keys (from ESP32: t, h, s) and long keys (temp, hum, smoke_level)
        $normalized = $this->normalizeKeys($this->data);

        Log::info("ProcessSensorData: Processing for sensor #{$sensor->id}", [
            'mac'  => $this->macAddress,
            'raw'  => $this->data,
            'normalized' => $normalized,
        ]);

        // 1. Update Cache — latest status (TTL 5 minutes)
        NodeStatusService::set($sensor->id, [
            'temp'   => $normalized['temp']   ?? null,
            'hum'    => $normalized['hum']    ?? null,
            'rssi'   => $normalized['rssi']   ?? null,
            'smoke'  => $normalized['smoke']  ?? null,
            'motion' => $normalized['motion'] ?? null,
            'voltage'=> $normalized['voltage']?? null,
            'light'  => $normalized['light']  ?? null,
            'ts'     => now()->toIso8601String(),
        ]);

        // 2. Save to MySQL for history (if node_id exists)
        if ($sensor->node_id) {
            $metricMap = [
                'temp'   => 'temperature',
                'hum'    => 'humidity',
                'smoke'  => 'smoke',
                'voltage'=> 'voltage',
                'light'  => 'light',
            ];

            foreach ($metricMap as $key => $metric) {
                if (isset($normalized[$key]) && $normalized[$key] !== null) {
                    SensorLog::create([
                        'node_id'     => $sensor->node_id,
                        'metric'      => $metric,
                        'value'       => $normalized[$key],
                        'recorded_at' => now(),
                    ]);
                }
            }
        }

        // 3. Check threshold alerts (temp > 30°C)
        if (($normalized['temp'] ?? 0) > 30) {
            Activity::create([
                'node_id'     => $sensor->node_id,
                'type'        => 'threshold_alert',
                'description' => "Suhu sensor {$this->macAddress} melebihi batas: {$normalized['temp']}°C",
                'meta'        => ['temp' => $normalized['temp'], 'threshold' => 30],
            ]);
        }

        // 4. Check smoke alert
        if (($normalized['smoke'] ?? 0) > 400) {
            Activity::create([
                'node_id'     => $sensor->node_id,
                'type'        => 'threshold_alert',
                'description' => "Asap terdeteksi di sensor {$this->macAddress}: level {$normalized['smoke']}",
                'meta'        => ['smoke' => $normalized['smoke'], 'threshold' => 400],
            ]);
        }
    }

    /**
     * Normalize short/long key names from ESP32 payloads.
     *
     * Supports: {"t":24.5,"h":50,"s":12} OR {"temp":24.5,"hum":50,"smoke_level":12}
     */
    private function normalizeKeys(array $data): array
    {
        $keyMap = [
            // short => normalized
            't'              => 'temp',
            'h'              => 'hum',
            's'              => 'smoke',
            'r'              => 'rssi',
            'm'              => 'motion',
            'v'              => 'voltage',
            'l'              => 'light',
            // long => normalized
            'temp'           => 'temp',
            'temperature'    => 'temp',
            'hum'            => 'hum',
            'humidity'       => 'hum',
            'smoke'          => 'smoke',
            'smoke_level'    => 'smoke',
            'rssi'           => 'rssi',
            'motion'         => 'motion',
            'motion_detected'=> 'motion',
            'voltage'        => 'voltage',
            'light'          => 'light',
        ];

        $normalized = [];
        foreach ($data as $key => $value) {
            $normalKey = $keyMap[$key] ?? $key;
            $normalized[$normalKey] = $value;
        }

        return $normalized;
    }
}
