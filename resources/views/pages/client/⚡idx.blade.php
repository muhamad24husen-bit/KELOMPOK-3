<?php

use Livewire\Component;
use App\Models\BEMS\Room;
use App\Models\BEMS\Staff;
use App\Services\NodeStatusService;

new class extends Component {
    public function render()
    {
        $user = auth()->user();
        $client = $user->bemsClient;

        if (!$client) {
            return $this->view(['client' => null, 'rooms' => collect(), 'stats' => [], 'staffCount' => 0]);
        }

        $stats = NodeStatusService::getClientStats($client->id);

        $rooms = Room::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->withCount('sensors', 'nodes')
            ->get();

        $staffCount = Staff::where('client_id', $client->id)->where('is_active', true)->count();

        return $this->view([
            'client'     => $client,
            'rooms'      => $rooms,
            'stats'      => $stats,
            'staffCount' => $staffCount,
        ]);
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.10s>
    <header class="flex items-end justify-between mb-gutter">
        <div>
            <h1 class="font-h1 text-h1 text-on-background mb-1">{{ $client->name ?? 'Client' }} Dashboard</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Building management overview.</p>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-surface-container border border-outline-variant/30">
            <div class="w-2 h-2 rounded-full bg-primary animate-pulse-slow"></div>
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Live</span>
        </div>
    </header>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-gutter mb-margin-page">
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20 shadow-sm">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide mb-2">Rooms</p>
            <span class="font-display text-display text-on-background">{{ $stats['total_rooms'] ?? 0 }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20 shadow-sm">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide mb-2">Online Nodes</p>
            <span class="font-display text-display text-primary">{{ $stats['online_nodes'] ?? 0 }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20 shadow-sm">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide mb-2">Offline Nodes</p>
            <span class="font-display text-display text-error">{{ $stats['offline_nodes'] ?? 0 }}</span>
        </div>
        <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20 shadow-sm">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide mb-2">Staff</p>
            <span class="font-display text-display text-on-background">{{ $staffCount }}</span>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="flex gap-4 mb-margin-page">
        <a href="{{ route('client.rooms') }}"
            class="flex items-center gap-2 px-4 py-2 bg-primary-container text-on-primary-container rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-[18px]">meeting_room</span>
            Manage Rooms
        </a>
        <a href="{{ route('client.staff') }}"
            class="flex items-center gap-2 px-4 py-2 bg-surface-container border border-outline-variant/30 text-on-surface rounded-lg font-label-md text-label-md hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-[18px]">group</span>
            Manage Staff
        </a>
    </div>

    <!-- Room Grid -->
    <h2 class="font-h3 text-h3 text-on-surface mb-unit-md">Rooms</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
        @forelse($rooms as $room)
            <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20 shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-on-surface-variant">{{ $room->icon ?? 'meeting_room' }}</span>
                    <span class="font-label-md text-label-md text-on-surface">{{ $room->name }}</span>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    {{ $room->sensors_count }} sensors · {{ $room->nodes_count }} nodes · Floor {{ $room->floor }}
                </p>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-surface-container rounded-xl border border-dashed border-outline-variant">
                <span class="material-symbols-outlined text-3xl text-outline mb-2 block">meeting_room</span>
                <p class="text-on-surface-variant">No rooms registered yet.</p>
            </div>
        @endforelse
    </div>
</div>
