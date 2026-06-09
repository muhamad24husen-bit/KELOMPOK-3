<?php

use Livewire\Component;
use App\Models\BEMS\Room;
use App\Services\NodeStatusService;

new class extends Component {
    public $room;
    public $mqttStatus = [];

    public function mount(Room $room)
    {
        $this->room = $room->load('sensors');
    }

    public function render()
    {
        // Attach live cache data & get MQTT status
        $this->mqttStatus = NodeStatusService::getSubscriberStatus();

        foreach ($this->room->sensors as $sensor) {
            $sensor->live = NodeStatusService::get($sensor->id);
        }

        return $this->view(['room' => $this->room, 'sensors' => $this->room->sensors]);
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.3s>
    <header class="mb-gutter flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="font-h1 text-h1 text-on-background mb-1">Monitor: {{ $room->name }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">
                Floor {{ $room->floor }} · {{ $sensors->count() }} sensors
            </p>
        </div>
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
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
        @forelse($sensors as $sensor)
            @php
                $liveKey = match($sensor->measurement_type) {
                    'temperature' => 'temp',
                    'humidity' => 'hum',
                    'gas', 'smoke' => 'smoke',
                    'motion', 'counter' => 'motion',
                    'voltage' => 'voltage',
                    'light' => 'light',
                    default => $sensor->measurement_type,
                };
                $val = $sensor->live[$liveKey] ?? '—';
                $unit = $sensor->unit == 'celsius' ? '°C' : ($sensor->unit == 'percent' ? '%' : $sensor->unit);

                // Freshness detection
                $cachedAt = $sensor->live['_cached_at'] ?? null;
                $isStale = false;
                $lastUpdated = null;
                if ($cachedAt) {
                    $cachedTime = \Carbon\Carbon::parse($cachedAt);
                    $isStale = $cachedTime->diffInSeconds(now()) > 60;
                    $lastUpdated = $cachedTime->diffForHumans();
                }
            @endphp
            <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20 relative">
                {{-- Stale data indicator --}}
                @if($cachedAt && $isStale)
                    <div class="absolute -top-2 -right-2 z-10">
                        <span class="flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                            <span class="material-symbols-outlined text-[12px]">schedule</span>
                            Stale
                        </span>
                    </div>
                @endif

                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-2">{{ ucfirst($sensor->measurement_type) }}</p>
                <span class="font-display text-display text-on-background">{{ $val }}{{ is_numeric($val) ? $unit : '' }}</span>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1 font-mono text-xs">{{ $sensor->mac_address }}</p>

                {{-- Last updated timestamp --}}
                @if($cachedAt)
                    <p class="text-[10px] text-on-surface-variant/50 mt-1 font-mono">
                        Updated {{ $lastUpdated }}
                    </p>
                @endif
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-surface-container rounded-xl border border-dashed border-outline-variant">
                <span class="material-symbols-outlined text-3xl text-outline mb-2 block">sensors_off</span>
                <p class="text-on-surface-variant">No sensors in this room.</p>
            </div>
        @endforelse
    </div>
</div>
