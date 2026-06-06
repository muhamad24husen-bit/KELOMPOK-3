<?php

namespace App\Services;

use App\Models\BEMS\Node;
use Illuminate\Support\Facades\Log;

class MqttService
{
    /**
     * Publish provisioning configuration to the ESP32 via MQTT.
     *
     * Topic: bnsms/discovery/response/{mac_address}
     * Payload: JSON with approved status, telemetry topic, command topic, and interval.
     */
    public function publishProvisioningConfig(Node $node): bool
    {
        $mac   = strtolower(str_replace([':', '-'], '', $node->device_id));
        $topic = "bnsms/discovery/response/{$mac}";

        $payload = json_encode([
            'status'          => 'approved',
            'node_id'         => $node->id,
            'node_name'       => $node->name,
            'telemetry_topic' => $node->mqtt_telemetry_topic,
            'command_topic'   => $node->mqtt_command_topic,
            'interval_ms'     => 5000,
            'io_mapping'      => $node->io_mapping ?? [],
        ]);

        try {
            $host     = config('mqtt.host', '127.0.0.1');
            $port     = (int) config('mqtt.port', 1883);
            // Use unique client_id to avoid conflicts with subscriber
            $clientId = config('mqtt.client_id', 'laravel-provisioner') . '-pub-' . uniqid();

            if (class_exists(\PhpMqtt\Client\MqttClient::class)) {
                $mqtt = new \PhpMqtt\Client\MqttClient($host, $port, $clientId);
                $mqtt->connect();
                $mqtt->publish($topic, $payload, 1); // QoS 1 = at least once
                $mqtt->disconnect();

                Log::info("✓ MQTT provisioning config published", [
                    'topic'   => $topic,
                    'node_id' => $node->id,
                    'payload' => $payload,
                ]);

                return true;
            }

            // Fallback: log the payload (useful during development without broker)
            Log::warning("MQTT client not installed. Provisioning payload logged.", [
                'topic'   => $topic,
                'payload' => $payload,
                'node_id' => $node->id,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("MQTT publish failed: {$e->getMessage()}", [
                'topic'   => $topic,
                'node_id' => $node->id,
            ]);

            return false;
        }
    }

    /**
     * Publish a generic command to a node via MQTT.
     */
    public function publishCommand(string $topic, array $payload): bool
    {
        try {
            $host     = config('mqtt.host', '127.0.0.1');
            $port     = (int) config('mqtt.port', 1883);
            $clientId = config('mqtt.client_id', 'laravel-cmd') . '-' . uniqid();

            if (class_exists(\PhpMqtt\Client\MqttClient::class)) {
                $mqtt = new \PhpMqtt\Client\MqttClient($host, $port, $clientId);
                $mqtt->connect();
                $mqtt->publish($topic, json_encode($payload), 1);
                $mqtt->disconnect();

                Log::info("MQTT command published", ['topic' => $topic]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("MQTT command failed: {$e->getMessage()}", ['topic' => $topic]);
            return false;
        }
    }
}
