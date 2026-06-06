<?php

use Livewire\Component;
use App\Models\BEMS\Sensor;
use App\Models\BEMS\Room;
use Mary\Traits\Toast;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

new class extends Component
{
    use Toast;

    public bool $show = false;
    public $sensor_id;
    public $room_id;

    public $mac_address = '';
    public $type = '';
    public $floor = '';
    public $measurement_type = 'temperature';
    public $unit = 'celsius';
    public $visualization_type = 'line';
    public $is_enabled = true;

    public function rules()
    {
        return [
            'mac_address' => ['required', 'string', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'type' => 'required|string',
            'floor' => 'nullable|string',
            'measurement_type' => 'required|string',
            'unit' => 'required|string',
            'visualization_type' => 'required|string',
        ];
    }

    #[On('show-add-sensor')]
    public function showDrawer($roomId)
    {
        $this->room_id = $roomId;
        $this->sensor_id = null;
        $this->reset(['mac_address', 'type', 'floor', 'measurement_type', 'unit', 'visualization_type', 'is_enabled']);
        $this->show = true;
    }

    #[On('edit-sensor')]
    public function editSensor($id)
    {
        $sensor = Sensor::findOrFail($id);
        $this->sensor_id = $sensor->id;
        $this->room_id = $sensor->room_id;
        $this->mac_address = $sensor->mac_address;
        $this->type = $sensor->type;
        $this->floor = $sensor->floor;
        $this->measurement_type = $sensor->measurement_type;
        $this->unit = $sensor->unit;
        $this->visualization_type = $sensor->visualization_type;
        $this->is_enabled = $sensor->is_enabled;
        $this->show = true;
    }

    /**
     * Computed property: generate topic preview from hierarchy.
     * Updates live as user types MAC address.
     */
    public function getPreviewTopicProperty(): string
    {
        if (!$this->room_id || strlen($this->mac_address) < 17) {
            return '';
        }

        $room = Room::with('client')->find($this->room_id);
        if (!$room || !$room->client) {
            return '';
        }

        $clientSlug = $room->client->slug ?? Str::slug($room->client->code ?? 'client');
        $roomSlug   = $room->slug ?? Str::slug($room->name);
        $mac        = strtolower(str_replace([':', '-'], '', $this->mac_address));

        return "{$clientSlug}/{$roomSlug}/{$mac}/telemetry";
    }

    public function save()
    {
        $this->validate();

        $data = [
            'room_id' => $this->room_id,
            'mac_address' => strtoupper($this->mac_address),
            'type' => $this->type,
            'floor' => $this->floor,
            'measurement_type' => $this->measurement_type,
            'unit' => $this->unit,
            'visualization_type' => $this->visualization_type,
            'is_enabled' => $this->is_enabled,
        ];

        if ($this->sensor_id) {
            $sensor = Sensor::find($this->sensor_id);
            $oldRoomId = $sensor->room_id;
            $sensor->update($data);

            // If room changed, regenerate MQTT topics (re-provision)
            if ($oldRoomId != $this->room_id) {
                $sensor->refreshMqttTopics();
                $this->success('Node updated. MQTT topics regenerated — device will re-provision.');
            } else {
                $this->success('Node sensor updated successfully.');
            }
        } else {
            $sensor = Sensor::create($data);
            // Generate and save MQTT topics for new sensor
            $sensor->refreshMqttTopics();
            $this->success('Node registered! Waiting for device to connect...');
        }

        $this->show = false;
        $this->dispatch('refreshMonitor');
    }
};
?>

<div>
    <x-drawer wire:model="show" :title="$sensor_id ? 'Edit Node Sensor' : 'Add New Node Sensor'" right class="w-full sm:w-[460px] bg-slate-900" separator>
        
        <div class="mb-6">
            <p class="font-body-sm text-body-sm text-slate-400">Configure hardware details and visualization parameters.</p>
        </div>

        <x-form wire:submit="save" class="space-y-8">
            
            {{-- Section 1: Hardware Identification --}}
            <div class="space-y-4">
                <h3 class="font-label-sm text-label-sm text-blue-400 uppercase tracking-wider border-b border-slate-800 pb-2">Hardware Info</h3>
                
                <x-input 
                    label="MAC Address" 
                    wire:model.live.debounce.500ms="mac_address" 
                    placeholder="00:1A:2B:3C:4D:5E" 
                    hint="Format: XX:XX:XX:XX:XX:XX"
                    required
                />

                {{-- MQTT Topic Preview (auto-generated from hierarchy) --}}
                @if($this->previewTopic)
                    <div class="rounded-lg bg-slate-800/60 border border-blue-500/20 p-3 space-y-2 animate-in fade-in duration-300">
                        <p class="text-[10px] text-blue-400 uppercase tracking-widest font-bold flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[14px]">router</span>
                            Auto-Generated MQTT Topics
                        </p>
                        <div>
                            <span class="text-[9px] text-slate-500 uppercase tracking-wider">ESP32 → Server (Publish)</span>
                            <p class="font-mono text-xs text-emerald-400 break-all mt-0.5">{{ $this->previewTopic }}</p>
                        </div>
                        <div>
                            <span class="text-[9px] text-slate-500 uppercase tracking-wider">Server → ESP32 (Command)</span>
                            <p class="font-mono text-xs text-amber-400 break-all mt-0.5">{{ str_replace('/telemetry', '/command', $this->previewTopic) }}</p>
                        </div>
                        <p class="text-[10px] text-slate-500 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px]">info</span>
                            Topik dikirim otomatis ke ESP32 saat pertama kali menyala.
                        </p>
                    </div>
                @endif

                <x-select 
                    label="Sensor Type" 
                    wire:model="type" 
                    :options="[
                        ['id' => 'Environmental', 'name' => 'Environmental (Temp/Humidity)'],
                        ['id' => 'Power', 'name' => 'Power Consumption Monitor'],
                        ['id' => 'Motion', 'name' => 'PIR Motion Detector'],
                        ['id' => 'Air Quality', 'name' => 'Air Quality (VOC/CO2)'],
                        ['id' => 'Door', 'name' => 'Contact Sensor'],
                    ]" 
                    placeholder="Select hardware type..." 
                    required 
                />

                <x-input label="Floor Level" wire:model="floor" placeholder="e.g., 3" />
            </div>

            {{-- Section 2: Measurement & Unit --}}
            <div class="space-y-4">
                <h3 class="font-label-sm text-label-sm text-blue-400 uppercase tracking-wider border-b border-slate-800 pb-2">Measurement Configuration</h3>
                
                <div class="grid grid-cols-2 gap-3">
                    <x-select 
                        label="Value Parsing"
                        wire:model.live="measurement_type" 
                        :options="[
                            ['id' => 'temperature', 'name' => 'Temperature'],
                            ['id' => 'humidity', 'name' => 'Humidity'],
                            ['id' => 'power', 'name' => 'Power / Energy'],
                            ['id' => 'voltage', 'name' => 'Voltage'],
                            ['id' => 'percentage', 'name' => 'Percentage'],
                        ]" 
                    />
                    
                    <x-select 
                        label="Unit"
                        wire:model="unit" 
                        :options="match($measurement_type) {
                            'temperature' => [['id' => 'celsius', 'name' => 'Celsius (°C)'], ['id' => 'fahrenheit', 'name' => 'Fahrenheit (°F)']],
                            'humidity', 'percentage' => [['id' => 'percent', 'name' => 'Percent (%)']],
                            'power' => [['id' => 'watt', 'name' => 'Watt (W)']],
                            'voltage' => [['id' => 'volt', 'name' => 'Volt (V)']],
                            default => []
                        }" 
                    />
                </div>
            </div>

            {{-- Section 3: Visualization --}}
            <div class="space-y-4">
                <h3 class="font-label-sm text-label-sm text-blue-400 uppercase tracking-wider border-b border-slate-800 pb-2">Default Visualization</h3>
                
                <div class="grid grid-cols-3 gap-3">
                    @php
                        $vizOptions = [
                            ['id' => 'line', 'icon' => 'show_chart', 'label' => 'Line Chart'],
                            ['id' => 'gauge', 'icon' => 'speed', 'label' => 'Gauge'],
                            ['id' => 'widget', 'icon' => 'dashboard', 'label' => 'Stat Widget'],
                            ['id' => 'light', 'icon' => 'lightbulb', 'label' => 'Light Control'],
                            ['id' => 'ac', 'icon' => 'mode_fan', 'label' => 'AC Control'],
                            ['id' => 'trends', 'icon' => 'monitoring', 'label' => 'Live Trends'],
                        ];
                    @endphp

                    @foreach($vizOptions as $option)
                        <label class="cursor-pointer group">
                            <input wire:model="visualization_type" type="radio" value="{{ $option['id'] }}" class="peer sr-only" />
                            <div class="border border-slate-800 rounded-md p-3 flex flex-col items-center gap-2 text-center peer-checked:border-blue-500 peer-checked:bg-blue-500/10 peer-checked:text-blue-400 transition-all hover:bg-slate-800">
                                <span class="material-symbols-outlined text-[24px] text-slate-500 group-hover:text-slate-300 peer-checked:text-blue-400 transition-colors">{{ $option['icon'] }}</span>
                                <span class="text-[10px] uppercase font-bold tracking-tighter text-slate-500 peer-checked:text-blue-400">{{ $option['label'] }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Section 4: Operational Status --}}
            <div class="pt-4 border-t border-slate-800">
                <x-checkbox label="Enable Sensor" wire:model="is_enabled" hint="Start ingesting data immediately upon creation." tight />
            </div>

            <x-slot name="actions">
                <x-button label="Cancel" @click="$wire.show = false" />
                <x-button label="Save Sensor" class="btn-primary" type="submit" spinner="save" icon="o-check" />
            </x-slot>

        </x-form>

    </x-drawer>
</div>
