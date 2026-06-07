<?php

namespace App\Console\Commands;

use App\Models\BEMS\Sensor;
use App\Jobs\ProcessSensorData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe to MQTT broker and process incoming sensor data & provisioning (auto-reconnect)';

    public function handle(): void
    {
        // Check if php-mqtt/client is available
        if (!class_exists(\PhpMqtt\Client\MqttClient::class)) {
            $this->error('php-mqtt/client is not installed. Run: composer require php-mqtt/client');
            return;
        }

        $host     = config('mqtt.host', '127.0.0.1');
        $port     = (int) config('mqtt.port', 1883);
        $clientId = config('mqtt.client_id', 'laravel-subscriber') . '-' . uniqid();
        $attempt  = 0;

        // ── Infinite reconnect loop ─────────────────────────────
        while (true) {
            $attempt++;
            $this->info("Connecting to MQTT broker {$host}:{$port} ... (attempt {$attempt})");

            try {
                $mqtt = new \PhpMqtt\Client\MqttClient($host, $port, $clientId);
                $mqtt->connect();

                // Reset attempt counter on successful connection
                $attempt = 0;
                $this->info('✓ Connected to MQTT broker!');

                // Update subscriber status → connected
                Cache::put('mqtt:subscriber:status', 'connected', 600);
                Cache::put('mqtt:subscriber:connected_at', now()->toIso8601String(), 600);

                // ── Handler 1: Provisioning Request dari ESP32 ─────────
                $mqtt->subscribe('provision/request', function (string $topic, string $payload) use ($mqtt) {
                    $data = json_decode($payload, true);
                    $mac  = strtoupper($data['mac'] ?? '');

                    if (!$mac) {
                        $this->warn('Received empty MAC in provision request');
                        return;
                    }

                    $sensor = Sensor::where('mac_address', $mac)->with('room.client')->first();

                    if (!$sensor) {
                        // MAC not registered — send rejection
                        $mqtt->publish("provision/config/{$mac}", json_encode([
                            'status'  => 'rejected',
                            'message' => 'MAC Address not registered. Contact administrator.',
                        ]));
                        $this->warn("Unregistered MAC attempt: {$mac}");
                        return;
                    }

                    // Ensure topics are generated
                    if (!$sensor->mqtt_pub_topic) {
                        $sensor->refreshMqttTopics();
                        $sensor->refresh();
                    }

                    // Send configuration to ESP32
                    $mqtt->publish("provision/config/{$mac}", json_encode([
                        'status'    => 'ok',
                        'pub_topic' => $sensor->mqtt_pub_topic,
                        'sub_topic' => $sensor->mqtt_sub_topic,
                        'interval'  => 5000, // ms
                        'node_name' => $sensor->measurement_type . '_' . $mac,
                    ]));

                    // Update provision status
                    $sensor->update([
                        'provision_status' => 'provisioned',
                        'provisioned_at'   => now(),
                        'is_enabled'       => true,
                    ]);

                    $this->info("Provisioned: {$mac} → {$sensor->mqtt_pub_topic}");
                }, 0);

                // ── Handler 2: Telemetry Data dari ESP32 ───────────────
                $mqtt->subscribe('+/+/+/telemetry', function (string $topic, string $payload) {
                    $data = json_decode($payload, true);

                    if (!$data) {
                        $this->warn("Invalid JSON from topic: {$topic}");
                        return;
                    }

                    // Extract mac from topic: {client}/{room}/{mac}/telemetry
                    $parts = explode('/', $topic);
                    $macRaw = $parts[2] ?? '';

                    // Convert back to MAC format (aabbccddeeff → AA:BB:CC:DD:EE:FF)
                    $mac = strtoupper(implode(':', str_split($macRaw, 2)));

                    ProcessSensorData::dispatchSync($mac, $data);
                    $this->line("📡 Received telemetry from: {$mac} → " . json_encode($data));

                    // Refresh subscriber status TTL on each message
                    Cache::put('mqtt:subscriber:status', 'connected', 600);
                }, 0);

                // ── Handler 3: Heartbeat ───────────────────────────────
                $mqtt->subscribe('+/+/+/heartbeat', function (string $topic, string $payload) {
                    $parts  = explode('/', $topic);
                    $macRaw = $parts[2] ?? '';
                    $mac    = strtoupper(implode(':', str_split($macRaw, 2)));

                    Sensor::where('mac_address', $mac)->update([
                        'is_enabled' => true,
                    ]);

                    $this->line("💓 Heartbeat from: {$mac}");
                }, 0);

                // ── Handler 4: Node Discovery (bnsms/discovery/request) ──
                $mqtt->subscribe('bnsms/discovery/request', function (string $topic, string $payload) {
                    $data = json_decode($payload, true);

                    if (!$data || empty($data['mac_address'])) {
                        $this->warn("Invalid discovery payload: {$payload}");
                        return;
                    }

                    $mac = strtoupper(trim($data['mac_address']));

                    \App\Models\BEMS\PendingNode::updateOrCreate(
                        ['mac_address' => $mac],
                        [
                            'chip_type'    => $data['chip_type']    ?? null,
                            'firmware_ver' => $data['firmware_ver']  ?? null,
                            'status'       => 'pending',
                        ]
                    );

                    $this->info("🆕 Discovery request: {$mac}");
                    Log::info("Discovery request received", ['mac' => $mac]);
                }, 0);

                $this->info('Subscribed to: provision/request, +/+/+/telemetry, +/+/+/heartbeat, bnsms/discovery/request');
                $this->info('Listening for messages... (Press Ctrl+C to stop)');

                // Blocking loop — keeps listening (will throw on disconnect)
                $mqtt->loop(true);

            } catch (\Exception $e) {
                // ── Mark subscriber as disconnected ──────────────────
                Cache::put('mqtt:subscriber:status', 'disconnected', 600);
                Cache::put('mqtt:subscriber:disconnected_at', now()->toIso8601String(), 600);

                $isConnectionRefused = str_contains($e->getMessage(), 'Connection refused');
                $isDataTransfer = str_contains(get_class($e), 'DataTransferException');

                if ($isDataTransfer) {
                    // Mid-session disconnect — connection was lost
                    $this->warn("⚠ MQTT connection lost: {$e->getMessage()}");
                    Log::warning('MQTT connection lost mid-session', ['error' => $e->getMessage()]);
                } elseif ($isConnectionRefused) {
                    $this->error("MQTT connection failed: {$e->getMessage()}");
                    Log::error("MQTT Subscriber failed: {$e->getMessage()}");
                    $this->newLine();
                    $this->warn('╔══════════════════════════════════════════════════════╗');
                    $this->warn('║  MQTT Broker (Mosquitto) tidak berjalan!             ║');
                    $this->warn('╚══════════════════════════════════════════════════════╝');
                    $this->line('');
                    $this->line('  <fg=cyan>Install Mosquitto:</>');
                    $this->line('  <fg=green>sudo apt install -y mosquitto mosquitto-clients</>');
                    $this->line('  <fg=green>sudo service mosquitto start</>');
                    $this->line('');
                } else {
                    $this->error("MQTT error: {$e->getMessage()}");
                    Log::error("MQTT Subscriber error: {$e->getMessage()}");
                }

                // Exponential backoff: 2s, 4s, 6s, ... max 30s
                $wait = min(($attempt + 1) * 2, 30);
                $this->info("🔄 Reconnecting in {$wait} seconds...");
                sleep($wait);
            }
        }
    }
}
