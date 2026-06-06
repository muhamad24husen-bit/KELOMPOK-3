<?php

use Livewire\Component;
use App\Models\BEMS\Sensor;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

    public function render()
    {
        $user = auth()->user();
        $clientId = $user->getTenantClientId();

        $sensors = Sensor::with('room.client')
            ->whereHas('room', fn ($q) => $q->where('client_id', $clientId))
            ->paginate(15);

        return $this->view(['sensors' => $sensors]);
    }

    public function reprovision($id)
    {
        $sensor = Sensor::findOrFail($id);
        $sensor->refreshMqttTopics();
        $this->success("MQTT topics regenerated for {$sensor->mac_address}. Waiting for device...");
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.5s>
    <header class="mb-gutter">
        <h1 class="font-h1 text-h1 text-on-background mb-1">Node Management</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Register and manage sensor nodes.</p>
    </header>

    <div class="bg-surface-container rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-outline-variant/20 bg-surface-container-high">
                    <th class="py-3 px-unit-lg font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">MAC Address</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Room</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">MQTT Topic</th>
                    <th class="py-3 px-unit-lg font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="font-body-sm text-body-sm divide-y divide-outline-variant/10">
                @forelse($sensors as $sensor)
                    <tr class="hover:bg-surface-container-highest/30 transition-colors">
                        <td class="py-3 px-unit-lg font-mono text-xs text-on-surface">{{ $sensor->mac_address }}</td>
                        <td class="py-3 px-unit-md text-on-surface-variant">{{ $sensor->room->name ?? 'N/A' }}</td>
                        <td class="py-3 px-unit-md">
                            @php
                                $pConfig = match($sensor->provision_status ?? 'pending') {
                                    'provisioned' => ['text-primary bg-primary-container/20 border-primary-container/50', 'Provisioned', 'check_circle'],
                                    'waiting_provision' => ['text-tertiary bg-tertiary-container/20 border-tertiary-container/50 animate-pulse', 'Waiting', 'hourglass_top'],
                                    default => ['text-on-surface-variant bg-surface-variant border-outline-variant/30', 'Pending', 'pending'],
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border font-label-sm text-label-sm {{ $pConfig[0] }}">
                                <span class="material-symbols-outlined text-[14px]">{{ $pConfig[2] }}</span>
                                {{ $pConfig[1] }}
                            </span>
                        </td>
                        <td class="py-3 px-unit-md">
                            <code class="text-[10px] text-on-surface-variant font-mono break-all">{{ $sensor->mqtt_pub_topic ?? '—' }}</code>
                        </td>
                        <td class="py-3 px-unit-lg text-right">
                            <button wire:click="reprovision({{ $sensor->id }})" wire:confirm="Regenerate MQTT topics?"
                                class="p-1.5 rounded hover:bg-surface-variant text-on-surface-variant hover:text-primary transition-colors"
                                title="Re-provision">
                                <span class="material-symbols-outlined text-[18px]">refresh</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12 text-center text-on-surface-variant">No sensors registered.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-unit-lg py-unit-sm border-t border-outline-variant/20 bg-surface-container-highest/20">
            {{ $sensors->links() }}
        </div>
    </div>
</div>
