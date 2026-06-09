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
            <h1 class="font-h1 text-h1 text-slate-800 dark:text-slate-100 mb-1">Operation Requests</h1>
            <p class="font-body-md text-body-md text-slate-500 dark:text-slate-400">Approve or reject control requests from viewers.</p>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse-slow"></div>
            <span class="font-label-sm text-label-sm text-slate-600 dark:text-slate-400 uppercase tracking-wider">Live</span>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm mb-margin-page">
        <div class="px-unit-lg py-unit-md border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 rounded-t-xl">
            <h3 class="font-h3 text-h3 text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-500">pending_actions</span>
                Pending Requests
                @if($pendingRequests->count())
                    <span class="ml-2 bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 text-xs px-2 py-0.5 rounded-full font-bold">{{ $pendingRequests->count() }}</span>
                @endif
            </h3>
        </div>

        <div class="divide-y divide-slate-200 dark:divide-slate-800">
            @forelse($pendingRequests as $req)
                <div class="px-unit-lg py-unit-md flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 flex items-center justify-center">
                            <span class="material-symbols-outlined text-amber-600 dark:text-amber-400">
                                {{ str_contains($req->action, 'ac') ? 'mode_fan' : (str_contains($req->action, 'light') ? 'lightbulb' : 'touch_app') }}
                            </span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-slate-800 dark:text-slate-200">
                                {{ ucfirst(str_replace('_', ' ', $req->action)) }}
                            </p>
                            <p class="font-body-sm text-body-sm text-slate-500 dark:text-slate-400">
                                By {{ $req->requestedBy->name }} · {{ $req->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="approve({{ $req->id }})" wire:confirm="Execute this command?"
                            class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-lg font-label-sm text-label-sm hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition-colors flex items-center gap-1 shadow-sm">
                            <span class="material-symbols-outlined text-[16px]">check</span> Approve
                        </button>
                        <button wire:click="reject({{ $req->id }})"
                            class="px-3 py-1.5 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 rounded-lg font-label-sm text-label-sm hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-colors flex items-center gap-1 shadow-sm">
                            <span class="material-symbols-outlined text-[16px]">close</span> Reject
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-slate-500 dark:text-slate-400">
                    <span class="material-symbols-outlined text-3xl mb-2 block text-slate-300 dark:text-slate-600">task_alt</span>
                    <p>No pending requests.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- History -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div class="px-unit-lg py-unit-md border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 rounded-t-xl">
            <h3 class="font-h3 text-h3 text-slate-800 dark:text-slate-200">Request History</h3>
        </div>

        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30">
                    <th class="py-3 px-unit-lg font-label-sm text-label-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider">Action</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider">Requested By</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider">Responded</th>
                </tr>
            </thead>
            <tbody class="font-body-sm text-body-sm divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($historyRequests as $req)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="py-3 px-unit-lg text-slate-800 dark:text-slate-200">{{ ucfirst(str_replace('_', ' ', $req->action)) }}</td>
                        <td class="py-3 px-unit-md text-slate-600 dark:text-slate-400">{{ $req->requestedBy->name }}</td>
                        <td class="py-3 px-unit-md">
                            @php
                                $sColor = match($req->status) {
                                    'executed' => 'text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-500/20 border-emerald-200 dark:border-emerald-500/20',
                                    'approved' => 'text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-500/20 border-emerald-200 dark:border-emerald-500/20',
                                    'rejected' => 'text-rose-700 dark:text-rose-400 bg-rose-100 dark:bg-rose-500/20 border-rose-200 dark:border-rose-500/20',
                                    default    => 'text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border font-label-sm text-label-sm {{ $sColor }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-unit-md text-slate-500 dark:text-slate-400">{{ $req->responded_at?->diffForHumans() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">No history yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
