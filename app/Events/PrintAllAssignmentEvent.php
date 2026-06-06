<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrintAllAssignmentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $status;
    public $message;
    public $fileUrl;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $status, string $message, ?string $fileUrl = null, int $userId)
    {
        $this->status = $status;
        $this->message = $message;
        $this->fileUrl = $fileUrl;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('print-channel'),
        ];
    }

    /**
     * The name of the event's broadcast channel.
     */
    public function broadcastAs(): string
    {
        return 'print.all-assignments.generated';
    }
}
