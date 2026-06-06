<?php

use Livewire\Component;
use App\Models\BEMS\Room;
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

        return $this->view(['rooms' => $rooms, 'stats' => $stats]);
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.10s>
    <header class="mb-gutter">
        <h1 class="font-h1 text-h1 text-on-background mb-1">Viewer Dashboard</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">View building status and submit control requests.</p>
    </header>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-gutter mb-margin-page">
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Rooms</p>
            <span class="font-display text-display text-on-background">{{ $stats['total_rooms'] ?? 0 }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Online</p>
            <span class="font-display text-display text-primary">{{ $stats['online_nodes'] ?? 0 }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase mb-1">Offline</p>
            <span class="font-display text-display text-error">{{ $stats['offline_nodes'] ?? 0 }}</span>
        </div>
    </div>

    <!-- Room Grid -->
    <div class="flex items-center justify-between mb-unit-md">
        <h2 class="font-h3 text-h3 text-on-surface">Rooms</h2>
        <a href="{{ route('viewer.request') }}"
            class="flex items-center gap-2 px-4 py-2 bg-primary-container text-on-primary-container rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            New Request
        </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
        @foreach($rooms as $room)
            <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-on-surface-variant">{{ $room->icon ?? 'meeting_room' }}</span>
                    <span class="font-label-md text-label-md text-on-surface">{{ $room->name }}</span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    {{ $room->sensors_count }} sensors · Floor {{ $room->floor }}
                </p>
            </div>
        @endforeach
    </div>
</div>
