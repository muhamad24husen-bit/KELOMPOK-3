<?php

namespace App\Jobs;

use App\Models\BEMS\Activity;
use App\Models\BEMS\Node;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectOfflineNodes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Find nodes that haven't sent a heartbeat in 2+ minutes
     * and mark them as offline.
     */
    public function handle(): void
    {
        $threshold = now()->subMinutes(2);

        $offlineNodes = Node::where('status', 'online')
            ->where('last_heartbeat', '<', $threshold)
            ->get();

        foreach ($offlineNodes as $node) {
            $node->update(['status' => 'offline']);

            Activity::create([
                'node_id'     => $node->id,
                'type'        => 'status_change',
                'description' => "Node {$node->name} ({$node->device_id}) tidak merespons — status berubah ke offline",
                'meta'        => [
                    'last_heartbeat' => $node->last_heartbeat?->toIso8601String(),
                    'threshold_minutes' => 2,
                ],
            ]);
        }
    }
}
