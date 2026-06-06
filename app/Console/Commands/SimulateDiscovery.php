<?php

namespace App\Console\Commands;

use App\Models\BEMS\PendingNode;
use Illuminate\Console\Command;

class SimulateDiscovery extends Command
{
    protected $signature   = 'mqtt:simulate-discovery
                              {--mac= : MAC address (random if omitted)}
                              {--chip=ESP32 : Chip type}
                              {--firmware=v1.0 : Firmware version}';

    protected $description = 'Simulate an ESP32 discovery request (for dev/testing without a real MQTT broker)';

    public function handle(): int
    {
        $mac = $this->option('mac')
            ?: strtoupper(implode(':', array_map(fn () => sprintf('%02X', random_int(0, 255)), range(1, 6))));

        $node = PendingNode::updateOrCreate(
            ['mac_address' => $mac],
            [
                'chip_type'    => $this->option('chip'),
                'firmware_ver' => $this->option('firmware'),
                'status'       => 'pending',
            ]
        );

        $this->info("✓ Simulated discovery for MAC: {$mac}");
        $this->table(
            ['Field', 'Value'],
            [
                ['MAC Address',     $node->mac_address],
                ['Chip Type',       $node->chip_type],
                ['Firmware',        $node->firmware_ver],
                ['Status',          $node->status],
                ['Created',         $node->created_at],
            ]
        );

        return self::SUCCESS;
    }
}
