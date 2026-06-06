<?php

use Livewire\Component;
use App\Models\BEMS\Room;
use App\Models\BEMS\OperationRequest;
use App\Services\NodeStatusService;

new class extends Component {
    public function render()
    {
        $user = auth()->user();
        $clientId = $user->getTenantClientId();

        $rooms = Room::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->withCount('sensors', 'nodes')
            ->get();

        $pendingCount = OperationRequest::where('status', 'pending')
            ->whereHas('node.room', fn ($q) => $q->where('client_id', $clientId))
            ->count();

        $stats = NodeStatusService::getClientStats($clientId);

        return $this->view([
            'rooms'        => $rooms,
            'pendingCount' => $pendingCount,
            'stats'        => $stats,
        ]);
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.10s>
    <header class="flex items-end justify-between mb-gutter">
        <div>
            <h1 class="font-h1 text-h1 text-on-background mb-1">Operator Dashboard</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Monitor rooms and manage control requests.</p>
        </div>
        @if($pendingCount > 0)
            <a href="{{ route('operator.requests') }}"
                class="flex items-center gap-2 px-4 py-2 bg-tertiary-container text-on-tertiary-container rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined">pending_actions</span>
                {{ $pendingCount }} Pending
            </a>
        @endif
    </header>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-gutter mb-margin-page">
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Rooms</p>
            <span class="font-display text-display text-on-background">{{ $stats['total_rooms'] ?? 0 }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Online Nodes</p>
            <span class="font-display text-display text-primary">{{ $stats['online_nodes'] ?? 0 }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Offline Nodes</p>
            <span class="font-display text-display text-error">{{ $stats['offline_nodes'] ?? 0 }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Pending Requests</p>
            <span class="font-display text-display text-tertiary">{{ $pendingCount }}</span>
        </div>
    </div>

    <!-- Room Grid -->
    <h2 class="font-h3 text-h3 text-on-surface mb-unit-md">Rooms</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
        @foreach($rooms as $room)
            <a href="{{ route('operator.monitor', $room->id) }}"
                class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20 hover:border-primary/40 transition-colors group">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">{{ $room->icon ?? 'meeting_room' }}</span>
                    <span class="font-label-md text-label-md text-on-surface">{{ $room->name }}</span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    {{ $room->sensors_count }} sensors · {{ $room->nodes_count }} nodes · Floor {{ $room->floor }}
                </p>
            </a>
        @endforeach
    </div>
</div>
