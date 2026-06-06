<?php

use Livewire\Component;
use App\Models\BEMS\OperationRequest;
use App\Models\BEMS\Node;
use App\Models\BEMS\Room;
use Mary\Traits\Toast;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component {
    use Toast;
    use AuthorizesRequests;

    public string $selectedAction = '';
    public ?int $selectedNodeId = null;
    public bool $showModal = false;

    public function render()
    {
        // TenantScope otomatis filter berdasarkan client_id
        $rooms = Room::with('nodes')->get();

        // My recent requests
        $myRequests = OperationRequest::with(['node', 'approvedBy'])
            ->where('requested_by', auth()->id())
            ->latest()
            ->limit(15)
            ->get();

        // Available actions
        $actions = [
            ['id' => 'ac_on',    'name' => 'Turn AC On',    'icon' => 'mode_fan'],
            ['id' => 'ac_off',   'name' => 'Turn AC Off',   'icon' => 'mode_fan_off'],
            ['id' => 'light_on', 'name' => 'Turn Light On', 'icon' => 'lightbulb'],
            ['id' => 'light_off','name' => 'Turn Light Off', 'icon' => 'light_off'],
        ];

        return $this->view([
            'rooms'      => $rooms,
            'myRequests' => $myRequests,
            'actions'    => $actions,
        ]);
    }

    public function openRequest($nodeId, $action)
    {
        $this->selectedNodeId = $nodeId;
        $this->selectedAction = $action;
        $this->showModal = true;
    }

    public function submitRequest()
    {
        $this->authorize('request assistance');

        if (!$this->selectedNodeId || !$this->selectedAction) {
            $this->error('Please select a node and action.');
            return;
        }

        OperationRequest::create([
            'node_id'      => $this->selectedNodeId,
            'requested_by' => auth()->id(),
            'action'       => $this->selectedAction,
            'status'       => 'pending',
        ]);

        $this->showModal = false;
        $this->reset(['selectedNodeId', 'selectedAction']);
        $this->success('Request submitted! Waiting for operator approval.');
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.5s>
    <!-- Header -->
    <div class="flex items-end justify-between mb-gutter">
        <div>
            <h1 class="font-h1 text-h1 text-on-background mb-1">Control Request</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">
                Submit control requests for approval by an operator.
            </p>
        </div>
    </div>

    <!-- Room Cards with Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter mb-margin-page">
        @forelse($rooms as $room)
            <div class="bg-surface-container rounded-xl border border-outline-variant/20 p-unit-lg shadow-sm">
                <div class="flex items-center gap-2 mb-unit-md">
                    <span class="material-symbols-outlined text-on-surface-variant">{{ $room->icon ?? 'meeting_room' }}</span>
                    <h3 class="font-label-md text-label-md text-on-surface">{{ $room->name }}</h3>
                    <span class="font-body-sm text-body-sm text-on-surface-variant ml-auto">Floor {{ $room->floor }}</span>
                </div>

                @if($room->nodes->count())
                    <div class="space-y-2">
                        @foreach($room->nodes as $node)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-surface-container-highest/30 border border-outline-variant/10">
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface">{{ $node->name ?? $node->device_id }}</p>
                                    <p class="text-[10px] text-on-surface-variant">{{ $node->status }}</p>
                                </div>
                                <div class="flex gap-1">
                                    @foreach($actions as $act)
                                        <button wire:click="openRequest({{ $node->id }}, '{{ $act['id'] }}')"
                                            class="p-1.5 rounded hover:bg-surface-variant text-on-surface-variant hover:text-on-surface transition-colors"
                                            title="{{ $act['name'] }}">
                                            <span class="material-symbols-outlined text-[16px]">{{ $act['icon'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-on-surface-variant italic">No nodes in this room.</p>
                @endif
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-surface-container rounded-xl border border-dashed border-outline-variant">
                <span class="material-symbols-outlined text-3xl text-outline mb-2 block">meeting_room</span>
                <p class="text-on-surface-variant">No rooms available.</p>
            </div>
        @endforelse
    </div>

    <!-- My Recent Requests -->
    <div class="bg-surface-container rounded-xl border border-outline-variant/30 shadow-sm">
        <div class="px-unit-lg py-unit-md border-b border-outline-variant/20 bg-surface-container-highest/50">
            <h3 class="font-h3 text-h3 text-on-surface">My Requests</h3>
        </div>
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-outline-variant/20 bg-surface-container-high">
                    <th class="py-3 px-unit-lg font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Action</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Responded By</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Time</th>
                </tr>
            </thead>
            <tbody class="font-body-sm text-body-sm divide-y divide-outline-variant/10">
                @forelse($myRequests as $req)
                    <tr class="hover:bg-surface-container-highest/30 transition-colors">
                        <td class="py-3 px-unit-lg text-on-surface">{{ ucfirst(str_replace('_', ' ', $req->action)) }}</td>
                        <td class="py-3 px-unit-md">
                            @php
                                $sColor = match($req->status) {
                                    'pending'  => 'text-tertiary bg-tertiary-container/20 border-tertiary-container/50',
                                    'executed'  => 'text-primary bg-primary-container/20 border-primary-container/50',
                                    'approved' => 'text-primary bg-primary-container/20 border-primary-container/50',
                                    'rejected' => 'text-error bg-error-container/20 border-error-container/50',
                                    default    => 'text-on-surface-variant',
                                };
                                $sIcon = match($req->status) {
                                    'pending'  => 'hourglass_top',
                                    'executed'  => 'check_circle',
                                    'approved' => 'check_circle',
                                    'rejected' => 'cancel',
                                    default    => 'help',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border font-label-sm text-label-sm {{ $sColor }}">
                                <span class="material-symbols-outlined text-[14px]">{{ $sIcon }}</span>
                                {{ ucfirst($req->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-unit-md text-on-surface-variant">{{ $req->approvedBy?->name ?? '—' }}</td>
                        <td class="py-3 px-unit-md text-on-surface-variant">{{ $req->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-on-surface-variant">No requests submitted yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Confirmation Modal -->
    <x-modal wire:model="showModal" title="Confirm Request" class="bg-surface-container">
        <p class="font-body-md text-body-md text-on-surface-variant mb-4">
            Submit <strong class="text-on-surface">{{ ucfirst(str_replace('_', ' ', $selectedAction)) }}</strong> request?
            This will be sent to an operator for approval.
        </p>
        <x-slot name="actions">
            <x-button label="Cancel" @click="$wire.showModal = false" />
            <x-button label="Submit Request" class="btn-primary" wire:click="submitRequest" spinner="submitRequest" icon="o-check" />
        </x-slot>
    </x-modal>
</div>
