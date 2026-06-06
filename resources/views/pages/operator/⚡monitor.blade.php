<?php

use Livewire\Component;
use App\Models\BEMS\Room;
use App\Services\NodeStatusService;

new class extends Component {
    public $room;

    public function mount(Room $room)
    {
        $this->room = $room->load('sensors');
    }

    public function render()
    {
        // Attach live Redis data
        foreach ($this->room->sensors as $sensor) {
            $sensor->live = NodeStatusService::get($sensor->id);
        }

        return $this->view(['room' => $this->room, 'sensors' => $this->room->sensors]);
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.3s>
    <header class="mb-gutter">
        <h1 class="font-h1 text-h1 text-on-background mb-1">Monitor: {{ $room->name }}</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">
            Floor {{ $room->floor }} · {{ $sensors->count() }} sensors
        </p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
        @forelse($sensors as $sensor)
            @php
                $val = $sensor->live[$sensor->measurement_type === 'temperature' ? 'temp' : 'hum'] ?? '—';
                $unit = $sensor->unit == 'celsius' ? '°C' : ($sensor->unit == 'percent' ? '%' : $sensor->unit);
            @endphp
            <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-2">{{ ucfirst($sensor->measurement_type) }}</p>
                <span class="font-display text-display text-on-background">{{ $val }}{{ is_numeric($val) ? $unit : '' }}</span>
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1 font-mono text-xs">{{ $sensor->mac_address }}</p>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-surface-container rounded-xl border border-dashed border-outline-variant">
                <span class="material-symbols-outlined text-3xl text-outline mb-2 block">sensors_off</span>
                <p class="text-on-surface-variant">No sensors in this room.</p>
            </div>
        @endforelse
    </div>
</div>
