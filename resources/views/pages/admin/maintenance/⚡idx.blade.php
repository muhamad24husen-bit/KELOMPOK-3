<?php

use Livewire\Component;
use App\Models\BEMS\Sensor;
use App\Services\NodeStatusService;
use Livewire\WithPagination;
use Spatie\LaravelPdf\Facades\Pdf;
use Mary\Traits\Toast;


new class extends Component {
    use WithPagination;

    public function render()
    {
        $sensors = Sensor::with('room.client')->paginate(10);

        $stats = [
            'total' => Sensor::count(),
            'online' => Sensor::where('is_enabled', true)->count(),
            'offline' => Sensor::where('is_enabled', false)->count(),
            'provisioned' => Sensor::where('provision_status', 'provisioned')->count(),
            'waiting' => Sensor::where('provision_status', 'waiting_provision')->count(),
        ];

        return $this->view(['sensors' => $sensors, 'stats' => $stats]);
    }

    public function ping($sensorId)
    {
        $sensor = Sensor::find($sensorId);

        if (!$sensor) {
            $this->error('Sensor tidak ditemukan.');
            return;
        }

        if (!$sensor->mqtt_sub_topic) {
            $this->warning('Sensor ini belum memiliki MQTT topic. Lakukan provisioning terlebih dahulu.');
            return;
        }

        try {
            NodeStatusService::publishCommand(
                $sensor->mqtt_sub_topic,
                'ping',
                ['ts' => now()->toIso8601String()]
            );
            $this->info("Ping dikirim ke {$sensor->mac_address}. Menunggu respons perangkat...");
        } catch (\Throwable $e) {
            $this->error('Gagal mengirim ping: ' . $e->getMessage());
        }
    }

    public function restart($sensorId)
    {
        $sensor = Sensor::find($sensorId);

        if (!$sensor) {
            $this->error('Sensor tidak ditemukan.');
            return;
        }

        if (!$sensor->mqtt_sub_topic) {
            $this->warning('Sensor ini belum memiliki MQTT topic. Lakukan provisioning terlebih dahulu.');
            return;
        }

        try {
            NodeStatusService::publishCommand(
                $sensor->mqtt_sub_topic,
                'restart',
                ['ts' => now()->toIso8601String()]
            );
            $this->warning("Perintah restart dikirim ke {$sensor->mac_address}. Perangkat akan reboot.");
        } catch (\Throwable $e) {
            $this->error('Gagal mengirim restart: ' . $e->getMessage());
        }
    }

    public function exportPdf()
    {
        $sensors = Sensor::with('room.client')->get();

        return response()->streamDownload(function () use ($sensors) {
            try {
                echo Pdf::view('pdf.maintenance-export', ['sensors' => $sensors])
                    ->format('a4')
                    ->withBrowsershot(function ($browsershot) {
                        $browsershot->noSandbox()->waitUntilNetworkIdle();
                    })
                    ->getBrowsershot()
                    ->pdf();
            } catch (\Throwable $e) {
                echo \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.maintenance-export', ['sensors' => $sensors])
                    ->setPaper('a4')
                    ->output();
            }
        }, 'maintenance-export.pdf');

    }
}; ?>


<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.5s>
    <!-- Page Header -->
    <div class="flex items-end justify-between mb-gutter">
        <div>
            <h1 class="font-h1 text-h1 text-slate-800 dark:text-on-background mb-1">Diagnostik BNSMS</h1>
            <p class="font-body-md text-body-md text-slate-500 dark:text-on-surface-variant">Real-time telemetry and network node health
                status.</p>
        </div>
        <div
            class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white dark:bg-surface-container border border-slate-200 dark:border-outline-variant/30">
            <div class="w-2 h-2 rounded-full bg-blue-600 dark:bg-primary animate-pulse-slow"></div>
            <span class="font-label-sm text-label-sm text-slate-500 dark:text-on-surface-variant uppercase tracking-wider">Live
                Monitoring</span>
        </div>
    </div>

    <!-- KPI Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-gutter mb-margin-page">
        <!-- Total Nodes -->
        <div
            class="bg-white dark:bg-surface-container rounded-xl p-unit-lg border border-slate-200 dark:border-outline-variant/20 flex flex-col justify-between shadow-sm dark:shadow-none relative overflow-hidden group transition-colors duration-300">
            <div class="absolute top-0 right-0 p-unit-md opacity-20 group-hover:opacity-40 transition-opacity">
                <span class="material-symbols-outlined text-4xl text-slate-500 dark:text-inherit">hub</span>
            </div>
            <p class="font-label-md text-label-md text-slate-500 dark:text-on-surface-variant uppercase tracking-wide mb-2">Total Nodes</p>
            <div class="flex items-baseline gap-2">
                <span class="font-display text-display text-slate-800 dark:text-on-background">{{ $stats['total'] }}</span>
            </div>
        </div>

        <!-- Online Nodes -->
        <div
            class="bg-white dark:bg-surface-container rounded-xl p-unit-lg border border-slate-200 dark:border-outline-variant/20 flex flex-col justify-between shadow-sm dark:shadow-none relative overflow-hidden transition-colors duration-300">
            <div class="absolute left-0 top-0 w-1 h-full bg-blue-600 dark:bg-primary"></div>
            <div class="absolute top-0 right-0 p-unit-md text-blue-600 dark:text-primary opacity-20">
                <span class="material-symbols-outlined text-4xl">check_circle</span>
            </div>
            <p class="font-label-md text-label-md text-slate-500 dark:text-on-surface-variant uppercase tracking-wide mb-2">Online</p>
            <div class="flex items-baseline gap-2">
                <span class="font-display text-display text-blue-600 dark:text-primary">{{ $stats['online'] }}</span>
                <span class="font-label-sm text-label-sm text-blue-600 dark:text-primary flex items-center">
                    <span class="material-symbols-outlined text-[14px] mr-1">arrow_upward</span>
                    {{ $stats['total'] > 0 ? number_format(($stats['online'] / $stats['total']) * 100, 1) : 0 }}%
                </span>
            </div>
        </div>

        <!-- Offline Nodes -->
        <div
            class="bg-white dark:bg-surface-container rounded-xl p-unit-lg border border-slate-200 dark:border-outline-variant/20 flex flex-col justify-between shadow-sm dark:shadow-none relative overflow-hidden transition-colors duration-300">
            <div class="absolute left-0 top-0 w-1 h-full bg-error-600 dark:bg-error"></div>
            <div class="absolute top-0 right-0 p-unit-md text-error-600 dark:text-error opacity-20">
                <span class="material-symbols-outlined text-4xl">error</span>
            </div>
            <p class="font-label-md text-label-md text-slate-500 dark:text-on-surface-variant uppercase tracking-wide mb-2">Offline</p>
            <div class="flex items-baseline gap-2">
                <span class="font-display text-display text-error-600 dark:text-error">{{ $stats['offline'] }}</span>
                <span class="font-label-sm text-label-sm text-error-600 dark:text-error flex items-center">
                    <span class="material-symbols-outlined text-[14px] mr-1">warning</span>
                    Kritis
                </span>
            </div>
        </div>

        <!-- Low Signal Nodes -->
        <div
            class="bg-white dark:bg-surface-container rounded-xl p-unit-lg border border-slate-200 dark:border-outline-variant/20 flex flex-col justify-between shadow-sm dark:shadow-none relative overflow-hidden transition-colors duration-300">
            <div class="absolute left-0 top-0 w-1 h-full bg-amber-500 dark:bg-tertiary"></div>
            <div class="absolute top-0 right-0 p-unit-md text-amber-600 dark:text-tertiary opacity-20">
                <span class="material-symbols-outlined text-4xl">network_wifi_1_bar</span>
            </div>
            <p class="font-label-md text-label-md text-slate-500 dark:text-on-surface-variant uppercase tracking-wide mb-2">Provisioned</p>
            <div class="flex items-baseline gap-2">
                <span class="font-display text-display text-amber-600 dark:text-tertiary">{{ $stats['provisioned'] }}</span>
                <span class="font-label-sm text-label-sm text-amber-600 dark:text-tertiary flex items-center">
                    {{ $stats['waiting'] > 0 ? $stats['waiting'] . ' waiting' : 'All configured' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Diagnostic Table Card -->
    <div
        class="bg-white dark:bg-surface-container rounded-xl border border-slate-200 dark:border-outline-variant/30 shadow-sm dark:shadow-none flex flex-col overflow-hidden transition-colors duration-300">
        <!-- Card Header -->
        <div
            class="px-unit-lg py-unit-md border-b border-slate-200 dark:border-outline-variant/20 flex items-center justify-between bg-white dark:bg-surface-container-highest/50">
            <h3 class="font-h3 text-h3 text-slate-800 dark:text-on-surface">Daftar Diagnostik Perangkat</h3>
            <div class="flex gap-2">
                <x-export-button />

            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-surface-container-high border-b border-slate-200 dark:border-outline-variant/30">
                        <th
                            class="py-3 px-unit-lg font-label-sm text-label-sm text-slate-600 dark:text-on-surface-variant uppercase tracking-wider">
                            ID Perangkat</th>
                        <th
                            class="py-3 px-unit-md font-label-sm text-label-sm text-slate-600 dark:text-on-surface-variant uppercase tracking-wider">
                            Lokasi</th>
                        <th
                            class="py-3 px-unit-md font-label-sm text-label-sm text-slate-600 dark:text-on-surface-variant uppercase tracking-wider">
                            Status</th>
                        <th
                            class="py-3 px-unit-md font-label-sm text-label-sm text-slate-600 dark:text-on-surface-variant uppercase tracking-wider">
                            Provision</th>
                        <th
                            class="py-3 px-unit-md font-label-sm text-label-sm text-slate-600 dark:text-on-surface-variant uppercase tracking-wider">
                            MQTT Topic</th>
                        <th
                            class="py-3 px-unit-md font-label-sm text-label-sm text-slate-600 dark:text-on-surface-variant uppercase tracking-wider">
                            Terakhir Terlihat</th>
                        <th
                            class="py-3 px-unit-lg font-label-sm text-label-sm text-slate-600 dark:text-on-surface-variant uppercase tracking-wider text-right">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="font-body-sm text-body-sm text-slate-700 dark:text-on-surface divide-y divide-slate-200 dark:divide-outline-variant/10">
                    @forelse($sensors as $sensor)
                        <tr
                            class="hover:bg-slate-50 dark:hover:bg-surface-container-highest/30 transition-colors group {{ !$sensor->is_enabled ? 'bg-red-50 dark:bg-error-container/5' : '' }}">
                            <td class="py-4 px-unit-lg">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded bg-slate-100 dark:bg-surface-variant border border-slate-200 dark:border-outline-variant/30 flex items-center justify-center text-slate-500 dark:text-on-surface-variant">
                                        <span class="material-symbols-outlined text-[18px]">
                                            {{ $sensor->type == 'temperature' ? 'thermostat' : ($sensor->type == 'door' ? 'door_sensor' : 'sensors') }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-label-md text-label-md text-slate-800 dark:text-on-surface">
                                            {{ $sensor->measurement_type }} {{ $sensor->room->name ?? 'Unknown' }}
                                        </div>
                                        <div class="font-body-sm text-body-sm text-slate-500 dark:text-on-surface-variant font-mono text-xs">
                                            {{ $sensor->mac_address }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-unit-md text-slate-600 dark:text-on-surface-variant">
                                {{ $sensor->room->client->name ?? 'N/A' }} <span class="mx-1 text-slate-400 dark:text-outline-variant">›</span>
                                {{ $sensor->room->name ?? 'N/A' }}
                            </td>
                            <td class="py-4 px-unit-md">
                                @if($sensor->is_enabled)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-blue-100 dark:bg-primary-container/20 border border-blue-200 dark:border-primary-container/50 text-blue-700 dark:text-primary font-label-sm text-label-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-primary"></span>
                                        Online
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-red-100 dark:bg-error-container/20 border border-red-200 dark:border-error-container/50 text-error-600 dark:text-error font-label-sm text-label-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-error-600 dark:bg-error"></span>
                                        Offline
                                    </span>
                                @endif
                            </td>
                            {{-- Provision Status --}}
                            <td class="py-4 px-unit-md">
                                @php
                                    $pStatus = $sensor->provision_status ?? 'pending';
                                    $pConfig = match($pStatus) {
                                        'provisioned' => ['bg-blue-100 dark:bg-primary-container/20 border-blue-200 dark:border-primary-container/50 text-blue-700 dark:text-primary', 'Provisioned', 'check_circle'],
                                        'waiting_provision', 'reprovisioning' => ['bg-amber-100 dark:bg-tertiary-container/20 border-amber-200 dark:border-tertiary-container/50 text-amber-700 dark:text-tertiary animate-pulse', 'Waiting...', 'hourglass_top'],
                                        default => ['bg-slate-100 dark:bg-surface-variant border-slate-200 dark:border-outline-variant/30 text-slate-600 dark:text-on-surface-variant', 'Pending', 'pending'],
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md border font-label-sm text-label-sm {{ $pConfig[0] }}">
                                    <span class="material-symbols-outlined text-[14px]">{{ $pConfig[2] }}</span>
                                    {{ $pConfig[1] }}
                                </span>
                            </td>
                            {{-- MQTT Topic --}}
                            <td class="py-4 px-unit-md">
                                @if($sensor->mqtt_pub_topic)
                                    <code class="text-[10px] text-slate-500 dark:text-on-surface-variant font-mono break-all leading-relaxed">
                                        {{ $sensor->mqtt_pub_topic }}
                                    </code>
                                @else
                                    <span class="text-[11px] text-slate-400 dark:text-outline-variant italic">Not assigned</span>
                                @endif
                            </td>
                            <td
                                class="py-4 px-unit-md {{ !$sensor->is_enabled ? 'text-error-600 dark:text-error' : 'text-slate-500 dark:text-on-surface-variant' }}">
                                {{ $sensor->is_enabled ? 'Baru saja' : $sensor->updated_at->diffForHumans() }}
                            </td>
                            <td class="py-4 px-unit-lg text-right">
                                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="ping({{ $sensor->id }})"
                                        class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-surface-variant text-slate-400 dark:text-on-surface-variant hover:text-blue-600 dark:hover:text-on-surface transition-colors"
                                        title="Ping">
                                        <span class="material-symbols-outlined text-[18px]">network_ping</span>
                                    </button>
                                    <button wire:click="restart({{ $sensor->id }})"
                                        class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-surface-variant text-slate-400 dark:text-on-surface-variant hover:text-error-600 dark:hover:text-error transition-colors"
                                        title="Restart">
                                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-500 dark:text-on-surface-variant">Belum ada perangkat yang
                                terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Table Pagination/Footer -->
        <div
            class="px-unit-lg py-unit-sm border-t border-slate-200 dark:border-outline-variant/20 flex items-center justify-between bg-white dark:bg-surface-container-highest/20">
            <div class="flex-1">
                {{ $sensors->links() }}
            </div>
        </div>
    </div>
</div>