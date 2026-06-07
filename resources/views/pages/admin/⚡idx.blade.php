<?php

use Livewire\Component;
use App\Models\BEMS\Room;
use App\Models\BEMS\Sensor;
use App\Models\BEMS\SensorLog;
use App\Models\BEMS\Activity;
use App\Services\NodeStatusService;

new class extends Component {
    public string $search = '';
    public string $floor = 'all';

    public function render()
    {
        $roomsQuery = Room::query();

        if ($this->search) {
            $roomsQuery->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->floor !== 'all') {
            $roomsQuery->where('floor', $this->floor);
        }

        $rooms = $roomsQuery->get();

        // Real activity log from DB
        $activities = Activity::with('node')
            ->latest()
            ->limit(10)
            ->get();

        // Room cards: attach latest Redis status if available
        foreach ($rooms as $room) {
            $room->sensors_data = $room->sensors->map(function ($sensor) {
                $status = NodeStatusService::get($sensor->id);
                return [
                    'id'   => $sensor->id,
                    'type' => $sensor->measurement_type,
                    'temp' => $status['temp'] ?? null,
                    'hum'  => $status['hum'] ?? null,
                ];
            });
        }

        return $this->view([
            'rooms'      => $rooms,
            'activities' => $activities,
        ]);
    }

    public function refreshLogs()
    {
        // Livewire automatically re-renders, so just toast
        $this->dispatch('toast', type: 'info', title: 'Logs Refreshed', description: 'Latest activities have been updated.');
    }
};
?>

<div class="space-y-unit-xl" wire:poll.10s>
    <!-- Header Section -->
    <header class="flex items-center justify-between mb-unit-lg">
        <div>
            <h1 class="font-h1 text-h1 text-slate-100 tracking-tight">Admin Dashboard</h1>
            <p class="font-body-sm text-body-sm text-slate-500 dark:text-slate-400 mt-1">Sistem Pemantauan Gedung Terpadu</p>
        </div>
        <div
            class="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 px-3 py-1.5 rounded-full border border-emerald-200 dark:border-emerald-500/20 font-label-sm text-label-sm">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="tracking-wide">LIVE</span>
        </div>
    </header>

    <!-- Middle Section: Room Status -->
    <section>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-unit-md gap-4">
            <h2 class="font-h3 text-h3 text-slate-800 dark:text-slate-200">Status Ruangan Real-time</h2>
            <div class="relative w-full md:w-64">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-400 text-sm">search</span>
                <input wire:model.live="search"
                    class="w-full bg-slate-50 dark:bg-[#1a1c26] border border-slate-200 dark:border-surface-variant rounded-lg py-2 pl-9 pr-4 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-500 font-body-sm text-body-sm transition-all"
                    placeholder="Cari ruangan..." type="text" />
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 mb-unit-md border-b border-slate-200 dark:border-surface-variant">
            <button wire:click="$set('floor', 'all')"
                class="px-4 py-2 font-label-md text-label-md transition-colors {{ $floor === 'all' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">Semua
                Lantai</button>
            <button wire:click="$set('floor', '1')"
                class="px-4 py-2 font-label-md text-label-md transition-colors {{ $floor === '1' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">Lantai
                1</button>
            <button wire:click="$set('floor', '2')"
                class="px-4 py-2 font-label-md text-label-md transition-colors {{ $floor === '2' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">Lantai
                2</button>
        </div>

        <!-- Room Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($rooms as $room)
                @php
                    $isDanger = $room->status === 'danger' || $room->status === 'DANGER';
                    $isStandby = $room->status === 'standby' || $room->status === 'STANDBY';
                    $isActive = $room->status === 'active' || $room->status === 'AKTIF';

                    $borderColor = $isDanger ? 'border-rose-200 dark:border-rose-500/50' : 'border-slate-200 dark:border-surface-variant';
                    $statusColor = $isDanger ? 'text-rose-600 dark:text-rose-400' : ($isStandby ? 'text-amber-600 dark:text-amber-400' : ($isActive ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400'));
                    $statusBg = $isDanger ? 'bg-rose-100 dark:bg-rose-500/10' : ($isStandby ? 'bg-amber-100 dark:bg-amber-500/10' : ($isActive ? 'bg-blue-100 dark:bg-blue-500/10' : 'bg-emerald-100 dark:bg-emerald-500/10'));
                    $statusBorder = $isDanger ? 'border-rose-200 dark:border-rose-500/20' : ($isStandby ? 'border-amber-200 dark:border-amber-500/20' : ($isActive ? 'border-blue-200 dark:border-blue-500/20' : 'border-emerald-200 dark:border-emerald-500/20'));

                    // Get real-time data from Redis via sensors_data
                    $tempData = $room->sensors_data->firstWhere('type', 'temperature');
                    $humData  = $room->sensors_data->firstWhere('type', 'humidity');
                    $currentTemp = $tempData['temp'] ?? null;
                    $currentHum  = $humData['hum'] ?? $tempData['hum'] ?? null;
                @endphp

                <div
                    class="bg-white dark:bg-[#1a1c26] p-4 rounded-xl border {{ $borderColor }} flex flex-col gap-3 relative overflow-hidden group hover:shadow-md transition-all duration-300">
                    @if($isDanger)
                        <div class="absolute top-0 right-0 w-24 h-24 bg-rose-100 dark:bg-rose-500/10 rounded-bl-full -z-0"></div>
                    @endif
                    <div class="flex justify-between items-start relative z-10">
                        <div class="flex items-center gap-2">
                            <span
                                class="material-symbols-outlined text-slate-500 dark:text-slate-400">{{ $room->icon ?? 'meeting_room' }}</span>
                            <span
                                class="font-label-md text-label-md text-slate-800 dark:text-slate-200 truncate max-w-[120px]">{{ $room->name }}</span>
                        </div>
                        <span
                            class="{{ $statusBg }} {{ $statusColor }} border {{ $statusBorder }} px-2 py-0.5 rounded font-label-sm text-[10px] tracking-wider uppercase">
                            {{ $room->status }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-2 relative z-10">
                        <div class="bg-slate-50 dark:bg-surface-dim/50 rounded p-2 border border-slate-200 dark:border-white/5">
                            <span class="block text-[10px] text-slate-500 uppercase tracking-wider mb-1">Suhu</span>
                            <span
                                class="font-body-md text-body-md {{ $isDanger ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300' }} font-semibold">
                                {{ $currentTemp !== null ? number_format($currentTemp, 1) . '°C' : '—' }}
                            </span>
                        </div>
                        <div class="bg-slate-50 dark:bg-surface-dim/50 rounded p-2 border border-slate-200 dark:border-white/5">
                            <span class="block text-[10px] text-slate-500 uppercase tracking-wider mb-1">Kelembapan</span>
                            <span class="font-body-md text-body-md text-slate-700 dark:text-slate-300">
                                {{ $currentHum !== null ? $currentHum . '%' : '—' }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div
                    class="col-span-full py-12 text-center bg-slate-50 dark:bg-[#1a1c26] rounded-xl border border-dashed border-slate-300 dark:border-surface-variant transition-colors duration-300">
                    <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 text-4xl mb-2">search_off</span>
                    <p class="text-slate-500 dark:text-slate-400">Tidak ada ruangan yang ditemukan.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Bottom Section: Activity Log (Dynamic from DB) -->
    <section>
        <div class="flex items-center justify-between mb-unit-md border-b border-slate-200 dark:border-surface-variant pb-2">
            <h2 class="font-h3 text-h3 text-slate-800 dark:text-slate-200">Log Aktivitas Terbaru</h2>
            <button wire:click="refreshLogs" wire:loading.attr="disabled"
                class="flex items-center gap-2 text-blue-600 dark:text-blue-200 hover:text-blue-700 dark:hover:text-blue-300 transition-colors text-xs font-medium uppercase tracking-wider disabled:opacity-50">
                <span wire:loading.remove wire:target="refreshLogs"
                    class="material-symbols-outlined text-[18px]">refresh</span>
                <span wire:loading wire:target="refreshLogs" class="loading loading-spinner loading-xs"></span>

            </button>
        </div>
        <div class="bg-white dark:bg-[#1a1c26] rounded-xl border border-slate-200 dark:border-surface-variant overflow-hidden shadow-sm dark:shadow-none transition-colors duration-300">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-surface-variant bg-slate-50 dark:bg-surface-dim">
                        <th class="py-3 px-4 font-label-sm text-[10px] text-slate-500 uppercase tracking-wider w-32">
                            Waktu</th>
                        <th class="py-3 px-4 font-label-sm text-[10px] text-slate-500 uppercase tracking-wider w-24">
                            Tipe</th>
                        <th class="py-3 px-4 font-label-sm text-[10px] text-slate-500 uppercase tracking-wider">
                            Aktivitas</th>
                    </tr>
                </thead>
                <tbody class="font-body-sm text-body-sm divide-y divide-slate-200 dark:divide-surface-variant">
                    @forelse($activities as $act)
                        @php
                            $typeColor = match($act->type) {
                                'threshold_alert' => 'text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20',
                                'status_change'   => 'text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20',
                                'command'         => 'text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/20',
                                default           => 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-500/10 border-slate-200 dark:border-slate-500/20',
                            };
                            $typeIcon = match($act->type) {
                                'threshold_alert' => 'warning',
                                'status_change'   => 'sync_alt',
                                'command'         => 'terminal',
                                default           => 'info',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-surface-dim/50 transition-colors">
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $act->created_at->format('H:i') }}
                                <span class="block text-[10px] text-slate-400 dark:text-slate-600">{{ $act->created_at->diffForHumans(short: true) }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] border {{ $typeColor }}">
                                    <span class="material-symbols-outlined text-[12px]">{{ $typeIcon }}</span>
                                    {{ str_replace('_', ' ', $act->type) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 {{ str_contains($act->type, 'alert') ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ $act->description }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-500">
                                <span class="material-symbols-outlined text-2xl mb-1 block">event_note</span>
                                Belum ada aktivitas tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>