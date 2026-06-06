<?php

use Livewire\Component;
use App\Models\BEMS\OperationRequest;
use App\Models\BEMS\Sensor;
use App\Models\BEMS\Activity;
use App\Services\NodeStatusService;
use Mary\Traits\Toast;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component {
    use Toast;
    use AuthorizesRequests;

    public function render()
    {
        // TenantScope otomatis filter berdasarkan client_id
        $pendingRequests = OperationRequest::with(['node', 'requestedBy'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        // Recent executed/rejected
        $historyRequests = OperationRequest::with(['node', 'requestedBy', 'approvedBy'])
            ->whereIn('status', ['executed', 'approved', 'rejected'])
            ->latest()
            ->limit(20)
            ->get();

        return $this->view([
            'pendingRequests' => $pendingRequests,
            'historyRequests' => $historyRequests,
        ]);
    }

    public function approve($requestId)
    {
        $this->authorize('approve requests');

        $req = OperationRequest::findOrFail($requestId);
        $sensor = Sensor::whereHas('node', fn ($q) => $q->where('id', $req->node_id))->first();

        // Execute MQTT command
        if ($sensor && $sensor->mqtt_sub_topic) {
            NodeStatusService::publishCommand(
                $sensor->mqtt_sub_topic,
                $req->action,
                $req->payload ?? []
            );
        }

        $req->update([
            'status'       => 'executed',
            'approved_by'  => auth()->id(),
            'responded_at' => now(),
        ]);

        // Audit trail
        Activity::create([
            'node_id'     => $req->node_id,
            'user_id'     => auth()->id(),
            'type'        => 'command',
            'description' => "Operator approved & executed '{$req->action}' — requested by {$req->requestedBy->name}",
            'meta'        => ['request_id' => $req->id, 'action' => $req->action],
        ]);

        $this->success("Request approved & command sent.");
    }

    public function reject($requestId)
    {
        $this->authorize('approve requests');

        $req = OperationRequest::findOrFail($requestId);

        $req->update([
            'status'       => 'rejected',
            'approved_by'  => auth()->id(),
            'responded_at' => now(),
        ]);

        Activity::create([
            'node_id'     => $req->node_id,
            'user_id'     => auth()->id(),
            'type'        => 'command',
            'description' => "Operator rejected '{$req->action}' request from {$req->requestedBy->name}",
        ]);

        $this->warning("Request rejected.");
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.5s>
    <!-- Header -->
    <div class="flex items-end justify-between mb-gutter">
        <div>
            <h1 class="font-h1 text-h1 text-on-background mb-1">Operation Requests</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Approve or reject control requests from viewers.</p>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-surface-container border border-outline-variant/30">
            <div class="w-2 h-2 rounded-full bg-primary animate-pulse-slow"></div>
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Live</span>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="bg-surface-container rounded-xl border border-outline-variant/30 shadow-sm mb-margin-page">
        <div class="px-unit-lg py-unit-md border-b border-outline-variant/20 bg-surface-container-highest/50">
            <h3 class="font-h3 text-h3 text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-tertiary">pending_actions</span>
                Pending Requests
                @if($pendingRequests->count())
                    <span class="ml-2 bg-tertiary-container text-on-tertiary-container text-xs px-2 py-0.5 rounded-full">{{ $pendingRequests->count() }}</span>
                @endif
            </h3>
        </div>

        <div class="divide-y divide-outline-variant/10">
            @forelse($pendingRequests as $req)
                <div class="px-unit-lg py-unit-md flex items-center justify-between hover:bg-surface-container-highest/30 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-tertiary-container/20 border border-tertiary-container/40 flex items-center justify-center">
                            <span class="material-symbols-outlined text-tertiary">
                                {{ str_contains($req->action, 'ac') ? 'mode_fan' : (str_contains($req->action, 'light') ? 'lightbulb' : 'touch_app') }}
                            </span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface">
                                {{ ucfirst(str_replace('_', ' ', $req->action)) }}
                            </p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">
                                By {{ $req->requestedBy->name }} · {{ $req->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="approve({{ $req->id }})" wire:confirm="Execute this command?"
                            class="px-3 py-1.5 bg-primary-container/20 border border-primary-container/50 text-primary rounded-lg font-label-sm text-label-sm hover:bg-primary-container/40 transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">check</span> Approve
                        </button>
                        <button wire:click="reject({{ $req->id }})"
                            class="px-3 py-1.5 bg-error-container/20 border border-error-container/50 text-error rounded-lg font-label-sm text-label-sm hover:bg-error-container/40 transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">close</span> Reject
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-3xl mb-2 block text-outline">task_alt</span>
                    <p>No pending requests.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- History -->
    <div class="bg-surface-container rounded-xl border border-outline-variant/30 shadow-sm">
        <div class="px-unit-lg py-unit-md border-b border-outline-variant/20 bg-surface-container-highest/50">
            <h3 class="font-h3 text-h3 text-on-surface">Request History</h3>
        </div>

        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-outline-variant/20 bg-surface-container-high">
                    <th class="py-3 px-unit-lg font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Action</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Requested By</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Responded</th>
                </tr>
            </thead>
            <tbody class="font-body-sm text-body-sm divide-y divide-outline-variant/10">
                @forelse($historyRequests as $req)
                    <tr class="hover:bg-surface-container-highest/30 transition-colors">
                        <td class="py-3 px-unit-lg text-on-surface">{{ ucfirst(str_replace('_', ' ', $req->action)) }}</td>
                        <td class="py-3 px-unit-md text-on-surface-variant">{{ $req->requestedBy->name }}</td>
                        <td class="py-3 px-unit-md">
                            @php
                                $sColor = match($req->status) {
                                    'executed' => 'text-primary bg-primary-container/20 border-primary-container/50',
                                    'approved' => 'text-primary bg-primary-container/20 border-primary-container/50',
                                    'rejected' => 'text-error bg-error-container/20 border-error-container/50',
                                    default    => 'text-on-surface-variant bg-surface-variant border-outline-variant/30',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border font-label-sm text-label-sm {{ $sColor }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-unit-md text-on-surface-variant">{{ $req->responded_at?->diffForHumans() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-on-surface-variant">No history yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
