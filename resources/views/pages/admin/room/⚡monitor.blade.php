<?php

use Livewire\Component;
use App\Models\BEMS\Room;
use App\Models\BEMS\Sensor;
use App\Models\BEMS\SensorLog;
use App\Services\NodeStatusService;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public $room;
    public $sensors = [];
    public $mqttStatus = [];

    public function mount(Room $room)
    {
        $this->room = $room;
        $this->loadSensors();
    }

    #[On('refreshMonitor')]
    public function refresh()
    {
        $this->loadSensors();
    }

    public function loadSensors()
    {
        $this->sensors = Sensor::where('room_id', $this->room->id)->get();
        $this->mqttStatus = NodeStatusService::getSubscriberStatus();

        // Attach real-time Redis data and historical chart data to each sensor
        foreach ($this->sensors as $sensor) {
            $sensor->live = NodeStatusService::get($sensor->id);

            // Ambil 12 data historis terakhir dari sensor_logs untuk grafik
            $logs = SensorLog::where('node_id', $sensor->node_id)
                ->where('metric', $sensor->measurement_type)
                ->orderBy('recorded_at', 'desc')
                ->limit(12)
                ->get()
                ->reverse()
                ->values();

            $sensor->chart_labels = $logs->pluck('recorded_at')
                ->map(fn ($t) => $t->format('H:i'))
                ->toArray();
            $sensor->chart_data = $logs->pluck('value')
                ->map(fn ($v) => (float) $v)
                ->toArray();
        }
    }

    public function toggleSensor($id)
    {
        $sensor = Sensor::find($id);
        if ($sensor) {
            $sensor->update(['is_enabled' => !$sensor->is_enabled]);
            $this->success('Sensor ' . ($sensor->is_enabled ? 'enabled' : 'disabled'));
            $this->loadSensors();
        }
    }

    public function deleteSensor($id)
    {
        $sensor = Sensor::find($id);
        if ($sensor) {
            $sensor->delete();
            $this->success('Sensor deleted successfully.');
            $this->loadSensors();
        }
    }

    /**
     * Kirim perintah kontrol ke perangkat via MQTT (AC, Light, dll.)
     */
    public function sendCommand($sensorId, string $action, array $payload = [])
    {
        $sensor = Sensor::find($sensorId);

        if (!$sensor || !$sensor->mqtt_sub_topic) {
            $this->error('Sensor tidak memiliki MQTT topic yang dikonfigurasi.');
            return;
        }

        try {
            NodeStatusService::publishCommand($sensor->mqtt_sub_topic, $action, $payload);
            $this->success("Perintah '{$action}' berhasil dikirim ke perangkat.");
        } catch (\Throwable $e) {
            $this->error('Gagal mengirim perintah: ' . $e->getMessage());
        }
    }
};
?>

<div wire:poll.3s="loadSensors">
    <!-- Phosphor Icons & Chart.js -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="font-h1 text-h1 text-slate-800 dark:text-slate-100 mb-1">Live Sensor Monitor: {{ $room->name }}</h2>
            <p class="font-body-sm text-body-sm text-slate-500 dark:text-slate-400">
                {{ $room->client->name ?? 'Unknown' }} - Floor {{ $room->floor }} | Real-time telemetry and control interface.
            </p>
        </div>
        <div class="flex gap-4 w-full md:w-auto items-center">
            {{-- MQTT Connection Status Badge --}}
            @php
                $status = $mqttStatus['status'] ?? 'unknown';
                $badgeClass = match($status) {
                    'connected' => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
                    'disconnected' => 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/20',
                    default => 'bg-slate-100 dark:bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-500/20',
                };
                $dotClass = match($status) {
                    'connected' => 'bg-emerald-500',
                    'disconnected' => 'bg-rose-500',
                    default => 'bg-slate-400',
                };
                $statusLabel = match($status) {
                    'connected' => 'MQTT Connected',
                    'disconnected' => 'MQTT Disconnected',
                    default => 'MQTT Unknown',
                };
            @endphp
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border text-xs font-semibold tracking-wide {{ $badgeClass }}">
                <span class="relative flex h-2.5 w-2.5">
                    @if($status === 'connected')
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $dotClass }} opacity-75"></span>
                    @endif
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 {{ $dotClass }}"></span>
                </span>
                {{ $statusLabel }}
            </div>
        </div>
    </div>

    <!-- Sensor Grid (Dynamic) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @forelse($sensors as $sensor)
            @php
                $liveKey = match($sensor->measurement_type) {
                    'temperature' => 'temp',
                    'humidity' => 'hum',
                    'gas', 'smoke' => 'smoke',
                    'motion', 'counter' => 'motion',
                    'voltage' => 'voltage',
                    'light' => 'light',
                    default => $sensor->measurement_type
                };
                $liveVal = $sensor->live[$liveKey] ?? null;
                $statVal = $liveVal ?? '—';

                // Freshness detection: check if data is stale (> 60 seconds old)
                $cachedAt = $sensor->live['_cached_at'] ?? null;
                $isStale = false;
                $lastUpdated = null;
                if ($cachedAt) {
                    $cachedTime = \Carbon\Carbon::parse($cachedAt);
                    $isStale = $cachedTime->diffInSeconds(now()) > 60;
                    $lastUpdated = $cachedTime->diffForHumans();
                }
            @endphp
            <div class="relative">
                {{-- Stale data indicator --}}
                @if($cachedAt && $isStale)
                    <div class="absolute -top-2 -right-2 z-10">
                        <span class="flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                            <span class="material-symbols-outlined text-[12px]">schedule</span>
                            Stale
                        </span>
                    </div>
                @endif

                @if($sensor->visualization_type == 'line')
                    <x-sensor.line-chart 
                        id="{{ $sensor->id }}" 
                        label="{{ ucfirst($sensor->measurement_type) }}" 
                        value="{{ $liveVal ?? '—' }}" 
                        unit="{{ $sensor->unit == 'celsius' ? '°C' : ($sensor->unit == 'percent' ? '%' : $sensor->unit) }}"
                        color="{{ $sensor->measurement_type == 'temperature' ? '#b4c5ff' : '#34d399' }}"
                        :data="array_combine(
                            count($sensor->chart_labels) ? $sensor->chart_labels : ['—'],
                            count($sensor->chart_data)   ? $sensor->chart_data   : [0]
                        )"
                    />
                @elseif($sensor->visualization_type == 'gauge')
                    <x-sensor.gauge 
                        id="{{ $sensor->id }}" 
                        label="{{ ucfirst($sensor->measurement_type) }}" 
                        value="{{ $statVal }}" 
                        unit="{{ $sensor->unit == 'celsius' ? '°C' : ($sensor->unit == 'percent' ? '%' : $sensor->unit) }}"
                        max="100"
                        color="#34d399"
                    />
                @elseif($sensor->visualization_type == 'light')
                    <div class="flex flex-col">
                        <x-sensor.light-control 
                            id="{{ $sensor->id }}" 
                            label="Light Status" 
                            :status="$sensor->is_enabled"
                        />
                        {{-- Tombol toggle light via MQTT --}}
                        <button
                            wire:click="sendCommand({{ $sensor->id }}, '{{ $sensor->is_enabled ? 'light_off' : 'light_on' }}')"
                            class="mt-2 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors
                                {{ $sensor->is_enabled
                                    ? 'bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-500/20'
                                    : 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-500/20'
                                }}"
                        >
                            {{ $sensor->is_enabled ? '⚡ Matikan Lampu' : '💡 Nyalakan Lampu' }}
                        </button>
                    </div>
                @elseif($sensor->visualization_type == 'trends')
                    <div class="col-span-full">
                        <x-sensor.live-trends 
                            id="{{ $sensor->id }}" 
                            title="{{ ucfirst($sensor->measurement_type) }} Trends" 
                            :labels="count($sensor->chart_labels) ? $sensor->chart_labels : ['—']"
                            :datasets="[
                                [
                                    'label' => ucfirst($sensor->measurement_type),
                                    'color' => '#b4c5ff',
                                    'data'  => count($sensor->chart_data) ? $sensor->chart_data : [0]
                                ]
                            ]"
                        />
                    </div>
                @elseif($sensor->visualization_type == 'ac')
                    <div class="flex flex-col">
                        <x-sensor.ac-control 
                            id="{{ $sensor->id }}" 
                            label="AC Control" 
                            status="{{ $sensor->is_enabled ? 'Active' : 'Inactive' }}"
                            target="{{ $sensor->meta['ac_target'] ?? 24 }}"
                        />
                        {{-- Tombol toggle AC via MQTT --}}
                        <div class="mt-2 flex gap-2">
                            <button
                                wire:click="sendCommand({{ $sensor->id }}, '{{ $sensor->is_enabled ? 'ac_off' : 'ac_on' }}')"
                                class="flex-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors
                                    {{ $sensor->is_enabled
                                        ? 'bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400'
                                        : 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400'
                                    }}"
                            >
                                {{ $sensor->is_enabled ? '❌ AC Off' : '❄️ AC On' }}
                            </button>
                        </div>
                    </div>
                @else
                    <x-sensor.stat-widget 
                        id="{{ $sensor->id }}" 
                        label="{{ ucfirst($sensor->measurement_type) }}" 
                        value="{{ $statVal }}" 
                        unit="{{ $sensor->unit == 'celsius' ? '°C' : ($sensor->unit == 'percent' ? '%' : $sensor->unit) }}"
                        icon="{{ $sensor->measurement_type == 'temperature' ? 'thermostat' : 'sensors' }}"
                        color="#b4c5ff"
                    />
                @endif

                {{-- Last updated timestamp --}}
                @if($cachedAt)
                    <p class="text-[10px] text-slate-400 dark:text-slate-600 mt-1 text-right font-mono">
                        Updated {{ $lastUpdated }}
                    </p>
                @endif
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-slate-50 dark:bg-surface-container border border-slate-200 dark:border-slate-800 rounded-xl transition-colors duration-300">
                <span class="material-symbols-outlined text-4xl text-slate-400 dark:text-slate-500 mb-2">sensors_off</span>
                <p class="text-slate-500 dark:text-slate-400">No sensors added to this room yet.</p>
            </div>
        @endforelse
    </div>

    
</div>
