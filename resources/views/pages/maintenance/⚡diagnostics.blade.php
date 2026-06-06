<?php

use Livewire\Component;
use App\Models\BEMS\Sensor;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function render()
    {
        $user = auth()->user();
        $clientId = $user->getTenantClientId();

        $sensors = Sensor::with('room.client')
            ->whereHas('room', fn ($q) => $q->where('client_id', $clientId))
            ->paginate(15);

        return $this->view(['sensors' => $sensors]);
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.5s>
    <header class="mb-gutter">
        <h1 class="font-h1 text-h1 text-on-background mb-1">Diagnostics</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Device health and network status.</p>
    </header>

    <div class="bg-surface-container rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-outline-variant/20 bg-surface-container-high">
                    <th class="py-3 px-unit-lg font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Device</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Room</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Provision</th>
                </tr>
            </thead>
            <tbody class="font-body-sm text-body-sm divide-y divide-outline-variant/10">
                @forelse($sensors as $sensor)
                    <tr class="hover:bg-surface-container-highest/30 transition-colors">
                        <td class="py-3 px-unit-lg">
                            <p class="font-label-md text-label-md text-on-surface">{{ $sensor->measurement_type }}</p>
                            <p class="font-mono text-xs text-on-surface-variant">{{ $sensor->mac_address }}</p>
                        </td>
                        <td class="py-3 px-unit-md text-on-surface-variant">{{ $sensor->room->name ?? '—' }}</td>
                        <td class="py-3 px-unit-md">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md {{ $sensor->is_enabled ? 'bg-primary-container/20 border border-primary-container/50 text-primary' : 'bg-error-container/20 border border-error-container/50 text-error' }} font-label-sm text-label-sm">
                                <span class="w-1.5 h-1.5 rounded-full {{ $sensor->is_enabled ? 'bg-primary' : 'bg-error' }}"></span>
                                {{ $sensor->is_enabled ? 'Online' : 'Offline' }}
                            </span>
                        </td>
                        <td class="py-3 px-unit-md text-on-surface-variant">{{ $sensor->provision_status ?? 'pending' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-12 text-center text-on-surface-variant">No sensors.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-unit-lg py-unit-sm border-t border-outline-variant/20">{{ $sensors->links() }}</div>
    </div>
</div>
