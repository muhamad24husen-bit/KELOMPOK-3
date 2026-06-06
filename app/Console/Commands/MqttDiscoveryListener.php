<?php

namespace App\Console\Commands;

use App\Models\BEMS\PendingNode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MqttDiscoveryListener extends Command
{
    protected $signature   = 'mqtt:listen-discovery
                              {--retry : Otomatis retry jika koneksi gagal}
                              {--max-retries=10 : Jumlah maksimal percobaan ulang}';
    protected $description = 'Subscribe to bnsms/discovery/request and register pending nodes';

    public function handle(): int
    {
        $host     = config('mqtt.host', '127.0.0.1');
        $port     = (int) config('mqtt.port', 1883);
        $clientId = config('mqtt.client_id', 'laravel-discovery') . '-' . uniqid();
        $topic    = 'bnsms/discovery/request';

        // Check if php-mqtt/client is available
        if (! class_exists(\PhpMqtt\Client\MqttClient::class)) {
            $this->error('php-mqtt/client is not installed. Run: composer require php-mqtt/client');
            $this->info('Tip: You can simulate discovery via the Artisan command below instead:');
            $this->info('  php artisan mqtt:simulate-discovery --mac=AA:BB:CC:DD:EE:FF');
            return self::FAILURE;
        }

        $retry      = $this->option('retry');
        $maxRetries = (int) $this->option('max-retries');
        $attempt    = 0;

        do {
            $attempt++;
            $this->info("Connecting to MQTT broker {$host}:{$port} ... (attempt {$attempt})");

            try {
                $mqtt = new \PhpMqtt\Client\MqttClient($host, $port, $clientId);
                $mqtt->connect();

                $this->info("✓ Connected! Subscribed to [{$topic}]. Listening for discovery requests...");

                $mqtt->subscribe($topic, function (string $topic, string $message) {
                    $this->processDiscoveryRequest($message);
                }, 1);

                $mqtt->loop(true); // blocking loop
                return self::SUCCESS;

            } catch (\Exception $e) {
                $this->error("MQTT connection failed: {$e->getMessage()}");
                Log::error("MQTT Discovery Listener failed: {$e->getMessage()}");

                if (str_contains($e->getMessage(), 'Connection refused')) {
                    $this->newLine();
                    $this->warn('╔══════════════════════════════════════════════════════╗');
                    $this->warn('║  MQTT Broker (Mosquitto) tidak berjalan!             ║');
                    $this->warn('╚══════════════════════════════════════════════════════╝');
                    $this->line('');
                    $this->line('  <fg=cyan>Solusi untuk WSL2/Ubuntu:</>');
                    $this->line('  1. Install:  <fg=green>sudo apt install -y mosquitto mosquitto-clients</>');
                    $this->line('  2. Start:    <fg=green>sudo service mosquitto start</>');
                    $this->line('  3. Verify:   <fg=green>mosquitto_pub -t test -m "hello"</>');
                    $this->line('');
                    $this->line('  <fg=cyan>Solusi untuk Docker:</>');
                    $this->line('  1. <fg=green>docker compose up -d mosquitto</>');
                    $this->line('');
                    $this->line('  <fg=cyan>Solusi untuk Windows:</>');
                    $this->line('  1. Download Mosquitto: <fg=blue>https://mosquitto.org/download/</>');
                    $this->line('  2. Install dan jalankan sebagai service');
                    $this->line('');
                }

                if ($retry && $attempt < $maxRetries) {
                    $wait = min($attempt * 3, 30); // backoff: 3s, 6s, 9s, ... max 30s
                    $this->info("Retry in {$wait} seconds...");
                    sleep($wait);
                } else {
                    return self::FAILURE;
                }
            }
        } while ($retry && $attempt < $maxRetries);

        return self::FAILURE;
    }

    /**
     * Process an incoming discovery request from an ESP32.
     */
    protected function processDiscoveryRequest(string $message): void
    {
        $data = json_decode($message, true);

        if (! $data || empty($data['mac_address'])) {
            $this->warn("Invalid discovery payload received: {$message}");
            return;
        }

        $mac = strtoupper(trim($data['mac_address']));

        // Upsert: create if new, update timestamp if already pending
        PendingNode::updateOrCreate(
            ['mac_address' => $mac],
            [
                'chip_type'    => $data['chip_type']    ?? null,
                'firmware_ver' => $data['firmware_ver']  ?? null,
                'status'       => 'pending',
            ]
        );

        $this->info("✓ Pending node registered/updated: {$mac}");
        Log::info("Discovery request received", ['mac' => $mac]);
    }
}

