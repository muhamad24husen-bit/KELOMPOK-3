<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BEMS\Client;
use App\Models\BEMS\Room;
use Livewire\Attributes\Url;
use Mary\Traits\Toast;
use Spatie\LaravelPdf\Facades\Pdf;
use Livewire\Attributes\On;


new class extends Component
{
    use Toast;
    use WithPagination;

    #[Url]
    public $client = '';

    public $search = '';

    #[On('refreshIndexRoom')]
    public function render()
    {
        $clients = Client::orderBy('name')->get();

        $rooms = Room::with('client')
            ->when($this->client, function($query) {
                $query->where('client_id', $this->client);
            })
            ->when($this->search, function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('floor', 'like', "%{$this->search}%");
            })
            ->paginate(10);

        // Hitung ruangan baru bulan ini untuk badge stats
        $newRoomsThisMonth = Room::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return $this->view(['clients' => $clients, 'rooms' => $rooms, 'newRoomsThisMonth' => $newRoomsThisMonth]);
    }

    public function delete($id)
    {
        $room = Room::find($id);
        if($room) {
            $room->delete();
            $this->success('Room deleted successfully.');
        }
    }

    public function liveMonitor($id)
    {
        $this->redirectRoute('admin.rooms.monitor', ['room' => $id]);
    }
    public function exportPdf()
    {
        $rooms = Room::with('client')
            ->when($this->client, function($query) {
                $query->where('client_id', $this->client);
            })
            ->when($this->search, function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('floor', 'like', "%{$this->search}%");
            })
            ->get();

        return response()->streamDownload(function () use ($rooms) {
            try {
                echo Pdf::view('pdf.rooms-export', ['rooms' => $rooms])
                    ->format('a4')
                    ->withBrowsershot(function ($browsershot) {
                        $browsershot->noSandbox()->waitUntilNetworkIdle();
                    })
                    ->getBrowsershot()
                    ->pdf();
            } catch (\Throwable $e) {
                echo \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rooms-export', ['rooms' => $rooms])
                    ->setPaper('a4')
                    ->output();
            }
        }, 'rooms-export.pdf');

    }
};

?>

<div>
    <!-- Header & Action -->
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="font-h1 text-h1 text-slate-800 dark:text-on-surface">Rooms Management</h2>
            <p class="font-body-md text-body-md text-slate-500 dark:text-slate-400 mt-1">Monitor and configure sensor deployment across all facility units.</p>
        </div>
        <div class="flex gap-3">
            <div class="relative min-w-[200px]">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-500 text-lg">filter_alt</span>
                <select wire:model.live="client" class="w-full appearance-none bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 rounded-lg py-2.5 pl-10 pr-10 text-slate-800 dark:text-on-surface text-label-md font-label-md focus:ring-2 focus:ring-blue-500/50 outline-none">
                    <option value="">All Buildings</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <span class="absolute right-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-500 pointer-events-none">expand_more</span>
            </div>
            <button wire:click="$dispatch('show-create-room')" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-label-md text-label-md flex items-center gap-2 transition-all shadow-lg shadow-blue-900/20 active:scale-95 border border-blue-500/50">
                <span class="material-symbols-outlined text-lg">add</span>
                Add New Room
            </button>
        </div>
    </div>

    <!-- Bento Grid Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-surface-container shadow-sm dark:shadow-none p-6 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-blue-500/30 transition-all group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-blue-100 dark:bg-blue-500/10 rounded-lg group-hover:bg-blue-200 dark:group-hover:bg-blue-500/20 transition-colors border border-blue-200 dark:border-blue-500/20">
                    <span class="material-symbols-outlined text-blue-600 dark:text-blue-500" data-icon="meeting_room">meeting_room</span>
                </div>
                @if($newRoomsThisMonth > 0)
                    <span class="text-xs font-bold text-blue-700 dark:text-blue-500 bg-blue-100 dark:bg-blue-500/10 px-2 py-1 rounded border border-blue-200 dark:border-blue-500/20">+{{ $newRoomsThisMonth }} this month</span>
                @endif
            </div>
            <p class="text-slate-500 text-label-sm uppercase tracking-wider mb-1">Total Rooms</p>
            <h3 class="text-3xl font-display text-slate-800 dark:text-on-surface">{{ \App\Models\BEMS\Room::count() }}</h3>
        </div>
        <div class="bg-white dark:bg-surface-container shadow-sm dark:shadow-none p-6 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-blue-500/30 transition-all group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-500/10 rounded-lg group-hover:bg-emerald-200 dark:group-hover:bg-emerald-500/20 transition-colors border border-emerald-200 dark:border-emerald-500/20">
                    <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-500" data-icon="layers">layers</span>
                </div>
            </div>
            <p class="text-slate-500 text-label-sm uppercase tracking-wider mb-1">Total Floors</p>
            <h3 class="text-3xl font-display text-slate-800 dark:text-on-surface">{{ \App\Models\BEMS\Room::distinct('floor')->count() }}</h3>
        </div>
        <div class="bg-white dark:bg-surface-container shadow-sm dark:shadow-none p-6 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-blue-500/30 transition-all group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-purple-100 dark:bg-purple-500/10 rounded-lg group-hover:bg-purple-200 dark:group-hover:bg-purple-500/20 transition-colors border border-purple-200 dark:border-purple-500/20">
                    <span class="material-symbols-outlined text-purple-600 dark:text-purple-400" data-icon="developer_board">developer_board</span>
                </div>
                <span class="text-xs font-bold text-purple-700 dark:text-purple-400 bg-purple-100 dark:bg-purple-500/10 px-2 py-1 rounded border border-purple-200 dark:border-purple-500/20">Active</span>
            </div>
            <p class="text-slate-500 text-label-sm uppercase tracking-wider mb-1">Nodes in Rooms</p>
            <h3 class="text-3xl font-display text-slate-800 dark:text-on-surface">{{ \App\Models\BEMS\Room::sum('total_nodes') }}</h3>
        </div>
        <div class="bg-white dark:bg-surface-container shadow-sm dark:shadow-none p-6 rounded-xl border border-error-200 dark:border-error/20 bg-gradient-to-br from-white dark:from-surface-container to-error-50 dark:to-error/5 hover:border-error-300 dark:hover:border-error/40 transition-all group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-2 bg-error-100 dark:bg-error/10 rounded-lg group-hover:bg-error-200 dark:group-hover:bg-error/20 transition-colors border border-error-200 dark:border-error/20">
                    <span class="material-symbols-outlined text-error-600 dark:text-error" data-icon="warning">warning</span>
                </div>
                <span class="flex h-2 w-2 rounded-full bg-error-600 dark:bg-error animate-pulse"></span>
            </div>
            <p class="text-slate-500 text-label-sm uppercase tracking-wider mb-1">Rooms with Alerts</p>
            <h3 class="text-3xl font-display text-error-600 dark:text-error">{{ \App\Models\BEMS\Room::where('status', 'CRITICAL')->count() }}</h3>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm dark:shadow-2xl">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
            <h3 class="text-h3 font-h3 text-slate-800 dark:text-on-surface">Detailed Room Inventory</h3>
            <div class="flex gap-2">
                <div class="relative mr-2">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                    <input wire:model.live="search" class="bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-sm rounded pl-9 pr-3 py-1.5 focus:outline-none focus:border-blue-500 transition-colors w-64 placeholder-slate-400 dark:placeholder-slate-500" placeholder="Search rooms..." type="text"/>
                </div>
                <x-export-button />

            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950/50 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-4 text-label-sm font-label-sm text-slate-500 uppercase tracking-wider">Room Name</th>
                        <th class="px-6 py-4 text-label-sm font-label-sm text-slate-500 uppercase tracking-wider">Building</th>
                        <th class="px-6 py-4 text-label-sm font-label-sm text-slate-500 uppercase tracking-wider">Floor</th>
                        <th class="px-6 py-4 text-label-sm font-label-sm text-slate-500 uppercase tracking-wider">Total Nodes</th>
                        <th class="px-6 py-4 text-label-sm font-label-sm text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-label-sm font-label-sm text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($rooms as $room)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-slate-100 dark:bg-slate-800 flex items-center justify-center border border-slate-200 dark:border-slate-700 text-blue-600 dark:text-blue-400">
                                        <span class="material-symbols-outlined text-lg" data-icon="{{ $room->icon ?? 'door_open' }}">{{ $room->icon ?? 'door_open' }}</span>
                                    </div>
                                    <span class="font-label-md text-slate-800 dark:text-on-surface">{{ $room->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-body-sm text-slate-600 dark:text-slate-400">{{ $room->client->name ?? 'Unknown' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-[11px] font-bold text-slate-700 dark:text-slate-300">{{ $room->floor }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-16 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="bg-blue-500 h-full" style="width: {{ min(($room->total_nodes / 50) * 100, 100) }}%"></div>
                                    </div>
                                    <span class="text-body-sm text-slate-800 dark:text-on-surface">{{ $room->total_nodes }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($room->status == 'OPERATIONAL')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-tight bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20">
                                        <span class="w-1 h-1 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                                        Operational
                                    </span>
                                @elseif($room->status == 'CRITICAL')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-tight bg-error-100 dark:bg-error/10 text-error-600 dark:text-error border border-error-200 dark:border-error/20">
                                        <span class="w-1 h-1 rounded-full bg-error-600 dark:bg-error animate-ping"></span>
                                        Critical
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-tight bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500 border border-amber-200 dark:border-amber-500/20">
                                        <span class="w-1 h-1 rounded-full bg-amber-500"></span>
                                        Maintenance
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="liveMonitor({{ $room->id }})" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors tooltip" data-tip="Live Monitor">
                                        <span class="material-symbols-outlined text-[20px]">sensors</span>
                                    </button>
                                    <button wire:click="$dispatch('edit-room', { id: {{ $room->id }} })" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors tooltip" data-tip="Edit">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <button wire:click="delete({{ $room->id }})" wire:confirm="Are you sure?" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-error-600 dark:hover:text-error transition-colors tooltip" data-tip="Delete">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                No rooms found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-white dark:bg-slate-900/30 border-t border-slate-200 dark:border-slate-800">
            {{ $rooms->links(data: ['scrollTo' => false]) }}
        </div>
    </div>


    <livewire:pages::admin.room.create />
    <livewire:pages::admin.room.edit />
</div>
