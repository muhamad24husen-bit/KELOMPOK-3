<?php

use Livewire\Component;
use App\Models\BEMS\Room;
use App\Models\BEMS\Sensor;
use App\Services\NodeStatusService;

new class extends Component {
    public function render()
    {
        $user = auth()->user();
        $clientId = $user->getTenantClientId();
        $stats = NodeStatusService::getClientStats($clientId);

        $rooms = Room::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->withCount('sensors', 'nodes')
            ->get();

        $sensorStats = [
            'total'       => Sensor::whereHas('room', fn ($q) => $q->where('client_id', $clientId))->count(),
            'provisioned' => Sensor::where('provision_status', 'provisioned')
                ->whereHas('room', fn ($q) => $q->where('client_id', $clientId))->count(),
            'waiting'     => Sensor::where('provision_status', 'waiting_provision')
                ->whereHas('room', fn ($q) => $q->where('client_id', $clientId))->count(),
        ];

        return $this->view([
            'rooms'       => $rooms,
            'stats'       => $stats,
            'sensorStats' => $sensorStats,
        ]);
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.10s>
    <header class="mb-gutter">
        <h1 class="font-h1 text-h1 text-on-background mb-1">Maintenance Dashboard</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Node registration, diagnostics, and provisioning status.</p>
    </header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-gutter mb-margin-page">
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Total Sensors</p>
            <span class="font-display text-display text-on-background">{{ $sensorStats['total'] }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Provisioned</p>
            <span class="font-display text-display text-primary">{{ $sensorStats['provisioned'] }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Waiting</p>
            <span class="font-display text-display text-tertiary">{{ $sensorStats['waiting'] }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Online Nodes</p>
            <span class="font-display text-display text-primary">{{ $stats['online_nodes'] ?? 0 }}</span>
        </div>
    </div>

    <div class="flex gap-4">
        <a href="{{ route('maintenance.nodes') }}"
            class="flex items-center gap-2 px-4 py-2 bg-primary-container text-on-primary-container rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-[18px]">hub</span>
            Manage Nodes
        </a>
        <a href="{{ route('maintenance.diagnostics') }}"
            class="flex items-center gap-2 px-4 py-2 bg-surface-container border border-outline-variant/30 text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-[18px]">troubleshoot</span>
            Diagnostics
        </a>
    </div>
</div>
