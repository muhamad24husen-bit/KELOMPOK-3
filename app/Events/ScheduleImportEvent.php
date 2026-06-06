<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ScheduleImportEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    public $status; public $message;

    public function __construct($s, $m) { $this->status = $s; $this->message = $m; }

    public function broadcastOn() { return [new Channel('scheduleImport')]; }
    public function broadcastAs() { return 'ScheduleImportEvent'; }
}
