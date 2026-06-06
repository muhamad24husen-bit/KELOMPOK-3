<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Laravel Reverb supports instant
class TeacherImportEvent implements ShouldBroadcastNow
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
        return [new Channel('teacherImport')];
    }

    // Sangat disarankan menambahkan ini agar nama event di sisi JS/Livewire jelas
    public function broadcastAs(): string
    {
        return 'TeacherImportEvent';
    }
}
