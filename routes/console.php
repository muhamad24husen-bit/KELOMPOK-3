<?php

use App\Jobs\DetectOfflineNodes;
use App\Models\BEMS\SensorLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled Tasks ────────────────────────────────────────────

// Detect nodes that haven't sent heartbeat in 2+ minutes
Schedule::job(new DetectOfflineNodes)->everyTwoMinutes()
    ->description('Detect and mark offline nodes');

// Prune sensor_logs older than 90 days to prevent table bloat
Schedule::command('model:prune', ['--model' => [SensorLog::class]])
    ->daily()
    ->description('Prune sensor logs older than 90 days');
