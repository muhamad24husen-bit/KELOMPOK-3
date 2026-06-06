<?php

namespace Database\Seeders;

use App\Models\BEMS\UiTemplate;
use Illuminate\Database\Seeder;

class UiTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'        => 'Server Room Pro',
                'icon'        => 'dns',
                'description' => 'High-precision monitoring for data centers and server rooms. Focuses on temperature, humidity, smoke detection, and power status.',
                'schema'      => [
                    ['key' => 'temp_key',    'label' => 'Temperature Sensor',    'placeholder' => 't',   'unit' => '°C'],
                    ['key' => 'hum_key',     'label' => 'Humidity Sensor',       'placeholder' => 'h',   'unit' => '%'],
                    ['key' => 'smoke_key',   'label' => 'Smoke/Gas Sensor',      'placeholder' => 's',   'unit' => 'ppm'],
                    ['key' => 'voltage_key', 'label' => 'Voltage Monitor',       'placeholder' => 'v',   'unit' => 'V'],
                    ['key' => 'danger_temp', 'label' => 'Danger Threshold (°C)', 'placeholder' => '30',  'unit' => '°C'],
                ],
                'default_mapping' => [
                    'temp_key'    => 't',
                    'hum_key'     => 'h',
                    'smoke_key'   => 's',
                    'voltage_key' => 'v',
                    'danger_temp' => 30.0,
                ],
            ],
            [
                'name'        => 'Smart Classroom',
                'icon'        => 'school',
                'description' => 'Comfort-focused monitoring for classrooms, meeting rooms, and offices. Tracks temperature, light intensity, and occupancy.',
                'schema'      => [
                    ['key' => 'temp_key',  'label' => 'Temperature Sensor',  'placeholder' => 't',   'unit' => '°C'],
                    ['key' => 'light_key', 'label' => 'Light Sensor (LDR)', 'placeholder' => 'l',   'unit' => 'lux'],
                    ['key' => 'pir_key',   'label' => 'PIR Motion Sensor',  'placeholder' => 'p',   'unit' => ''],
                ],
                'default_mapping' => [
                    'temp_key'  => 't',
                    'light_key' => 'l',
                    'pir_key'   => 'p',
                ],
            ],
            [
                'name'        => 'Security Lobby',
                'icon'        => 'shield',
                'description' => 'Security-oriented monitoring for lobbies, corridors, and entrances. Tracks motion, door status, and visitor counting.',
                'schema'      => [
                    ['key' => 'motion_key',  'label' => 'Motion Detector',         'placeholder' => 'm',  'unit' => ''],
                    ['key' => 'door_key',    'label' => 'Door Sensor (Magnetic)',   'placeholder' => 'd',  'unit' => ''],
                    ['key' => 'counter_key', 'label' => 'Visitor Counter',         'placeholder' => 'c',  'unit' => ''],
                ],
                'default_mapping' => [
                    'motion_key'  => 'm',
                    'door_key'    => 'd',
                    'counter_key' => 'c',
                ],
            ],
            [
                'name'        => 'Industrial Storage',
                'icon'        => 'warehouse',
                'description' => 'Safety monitoring for warehouses, parking areas, and industrial zones. Detects gas leaks, fire, and humidity levels.',
                'schema'      => [
                    ['key' => 'gas_key',  'label' => 'Gas Sensor (MQ)',     'placeholder' => 'g',  'unit' => 'ppm'],
                    ['key' => 'fire_key', 'label' => 'Flame Sensor',       'placeholder' => 'f',  'unit' => ''],
                    ['key' => 'hum_key',  'label' => 'Humidity Sensor',    'placeholder' => 'h',  'unit' => '%'],
                ],
                'default_mapping' => [
                    'gas_key'  => 'g',
                    'fire_key' => 'f',
                    'hum_key'  => 'h',
                ],
            ],
        ];

        foreach ($templates as $template) {
            UiTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
