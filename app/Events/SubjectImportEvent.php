<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class SubjectImportEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $status;
    public $message;

    public function __construct(string $status, string $message)
    {
        $this->status = $status;
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        // Channel khusus 'subjectImport'
        return [new Channel('subjectImport')];
    }

    public function broadcastAs(): string
    {
        return 'SubjectImportEvent';
    }
}
