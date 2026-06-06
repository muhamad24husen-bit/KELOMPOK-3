<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BEMS\Client;
use Mary\Traits\Toast;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;

new class extends Component {
    use Toast;
    use WithPagination;

    public $search = '';

    #[On('refreshIndexClient')]
    public function render()
    {
        $clients = Client::when($this->search, function ($query) {
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('gedung', 'like', "%{$this->search}%");
        })
            ->paginate(10);

        return $this->view(['clients' => $clients]);
    }

    public function deleteClient($clientId)
    {
        $client = Client::find($clientId);

        if ($client) {
            // hapus user juga
            User::find($client->user_id)?->delete();

            // hapus client
            $client->delete();

            $this->success('Client berhasil dihapus');
        }
    }

    public function loginAs($clientId)
    {
        $client = Client::find($clientId);

        $user = User::find($client->user_id);
        if ($user) {
            Auth::login($user);
            $this->redirectRoute('client');
        } else {
            $this->error('User account for this client not found.');
        }
    }

    public function rootToRoom($clientId)
    {
        $this->redirectRoute('admin.rooms', ['client' => $clientId], navigate: true);
    }

    public function exportPdf()
    {
        $clients = Client::when($this->search, function ($query) {
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('gedung', 'like', "%{$this->search}%");
        })->get();

        return response()->streamDownload(function () use ($clients) {
            try {
                // Coba Browsershot (butuh Chrome di server)
                echo Pdf::view('pdf.client-export', ['clients' => $clients])
                    ->format('a4')
                    ->withBrowsershot(function ($browsershot) {
                        $browsershot->noSandbox()
                            ->waitUntilNetworkIdle();
                    })
                    ->getBrowsershot()
                    ->pdf();
            } catch (\Throwable $e) {
                // Fallback ke DomPDF (tidak butuh Chrome)
                echo \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.client-export', ['clients' => $clients])
                    ->setPaper('a4')
                    ->output();
            }
        }, 'clients-export.pdf');

    }
};

?>

<div>
    <!-- Header & Breadcrumb -->
    <div class="flex items-end justify-between mb-8">
        <div class="space-y-1">
            <h2 class="font-h1 text-h1 text-slate-800 dark:text-on-surface">Client Buildings</h2>
            <p class="text-body-sm text-slate-500 dark:text-slate-400">Manage connected infrastructures and node health across all sites.
            </p>
        </div>
        <livewire:pages::admin.client.create />
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter mb-8">
        <div class="bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-none p-unit-lg rounded-xl flex flex-col justify-between transition-colors duration-300">
            <div class="flex justify-between items-start">
                <span class="text-label-sm uppercase tracking-widest text-slate-500">Total Buildings</span>
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400"
                    style="font-variation-settings: 'wght' 200;">domain</span>
            </div>
            <div class="mt-4">
                <h3 class="font-display text-slate-800 dark:text-on-surface">{{ \App\Models\BEMS\Client::count() }}</h3>
                <div class="flex items-center gap-1 text-label-sm text-emerald-600 dark:text-emerald-400 mt-1">
                    <span class="material-symbols-outlined text-[16px]">trending_up</span>
                    <span>+{{ \App\Models\BEMS\Client::whereMonth('created_at', now()->month)->count() }} this
                        month</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-none p-unit-lg rounded-xl flex flex-col justify-between transition-colors duration-300">
            <div class="flex justify-between items-start">
                <span class="text-label-sm uppercase tracking-widest text-slate-500">Total Rooms</span>
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400"
                    style="font-variation-settings: 'wght' 200;">meeting_room</span>
            </div>
            <div class="mt-4">
                <h3 class="font-display text-slate-800 dark:text-on-surface">{{ \App\Models\BEMS\Room::count() }}</h3>
                <div class="flex items-center gap-1 text-label-sm text-slate-500 mt-1">
                    <span>Across all locations</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-none p-unit-lg rounded-xl flex flex-col justify-between transition-colors duration-300">
            <div class="flex justify-between items-start">
                <span class="text-label-sm uppercase tracking-widest text-slate-500">Total Nodes</span>
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400"
                    style="font-variation-settings: 'wght' 200;">sensors</span>
            </div>
            <div class="mt-4">
                <h3 class="font-display text-slate-800 dark:text-on-surface">{{ \App\Models\BEMS\Sensor::count() }}</h3>
                <div class="flex items-center gap-1 text-label-sm text-emerald-600 dark:text-emerald-400 mt-1">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                    @php $totalSensors = \App\Models\BEMS\Sensor::count(); $onlineSensors = \App\Models\BEMS\Sensor::where('is_enabled', true)->count(); @endphp
                    <span>{{ $totalSensors > 0 ? number_format(($onlineSensors / $totalSensors) * 100, 1) : 0 }}% Connectivity</span>
                </div>
            </div>
        </div>

        <div
            class="bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-none p-unit-lg rounded-xl flex flex-col justify-between ring-1 ring-error/20 transition-colors duration-300">
            <div class="flex justify-between items-start">
                <span class="text-label-sm uppercase tracking-widest text-slate-500">Active Alerts</span>
                <span class="material-symbols-outlined text-error"
                    style="font-variation-settings: 'wght' 200;">warning</span>
            </div>
            <div class="mt-4">
                @php $alertCount = \App\Models\BEMS\Activity::where('type', 'threshold_alert')->where('created_at', '>=', now()->subDay())->count(); @endphp
                <h3 class="font-display text-error">{{ $alertCount }}</h3>
                <div class="flex items-center gap-1 text-label-sm text-error mt-1">
                    <span class="material-symbols-outlined text-[16px]">priority_high</span>
                    <span>Last 24 hours</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area: Data Table -->
    <div class="bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm dark:shadow-2xl transition-colors duration-300">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900/50">
            <div class="flex items-center gap-4">
                <h3 class="font-h3 text-slate-800 dark:text-on-surface">Building Directory</h3>
                <span class="bg-blue-100 dark:bg-blue-600/20 text-blue-700 dark:text-blue-400 px-2 py-0.5 rounded text-label-sm">Live Updates</span>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                    <input wire:model.live="search"
                        class="bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-sm rounded pl-9 pr-3 py-1.5 focus:outline-none focus:border-blue-500 transition-colors w-64 placeholder-slate-400 dark:placeholder-slate-500"
                        placeholder="Search..." type="text" />
                </div>
                <x-export-button />

            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950/50 border-b border-slate-200 dark:border-slate-800">
                        <th
                            class="px-6 py-4 text-label-sm text-slate-600 dark:text-slate-500 uppercase tracking-wider">
                            Building Name</th>
                        <th
                            class="px-6 py-4 text-label-sm text-slate-600 dark:text-slate-500 uppercase tracking-wider">
                            Address / Gedung</th>
                        <th
                            class="px-6 py-4 text-label-sm text-slate-600 dark:text-slate-500 uppercase tracking-wider text-center">
                            Rooms</th>
                        <th
                            class="px-6 py-4 text-label-sm text-slate-600 dark:text-slate-500 uppercase tracking-wider text-center">
                            Nodes</th>
                        <th
                            class="px-6 py-4 text-label-sm text-slate-600 dark:text-slate-500 uppercase tracking-wider">
                            Operational Status</th>
                        <th
                            class="px-6 py-4 text-label-sm text-slate-600 dark:text-slate-500 uppercase tracking-wider text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($clients as $client)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-blue-600 dark:text-blue-400 overflow-hidden border border-slate-200 dark:border-outline-variant/30">
                                        @if($client->thumbnail)
                                            <img class="w-full h-full object-cover"
                                                src="{{ asset('storage/' . $client->thumbnail) }}" />
                                        @else
                                            <span class="material-symbols-outlined text-[20px]">domain</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-label-md text-slate-800 dark:text-on-surface">{{ $client->name }}</div>
                                        <div class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">
                                            {{ $client->kelas ?? $client->code }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-body-sm text-slate-600 dark:text-slate-400">{{ $client->gedung ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-body-sm text-slate-800 dark:text-on-surface text-center">{{ $client->total_rooms ?? 0 }}
                            </td>
                            <td class="px-6 py-4 text-body-sm text-slate-800 dark:text-on-surface text-center">{{ $client->rooms()->count() }}</td>
                            <td class="px-6 py-4">
                                @if(\Carbon\Carbon::parse($client->expirity)->isPast())
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-error-100 dark:bg-error-container/30 text-error-600 dark:text-error text-label-sm font-medium border border-error-200 dark:border-error/20">
                                        <span class="material-symbols-outlined text-[14px]">error</span>
                                        Expired
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-label-sm font-medium border border-emerald-200 dark:border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                                        All Online
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="rootToRoom({{ $client->id }})" title="Go to Rooms"
                                        class="p-2 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-400/10 rounded-lg tooltip"
                                        data-tip="Rooms">
                                        <span class="material-symbols-outlined text-[20px]">meeting_room</span>
                                    </button>
                                    <button wire:click="$dispatch('enableEditClient', {clientId: {{ $client->id }} })"
                                        title="Edit Building"
                                        class="p-2 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-400/10 rounded-lg tooltip"
                                        data-tip="Edit">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                    <button wire:click="deleteClient({{ $client->id }})" title="Delete Building"
                                        class="p-2 text-slate-400 hover:text-error-600 dark:hover:text-error hover:bg-error-100 dark:hover:bg-error/10 rounded-lg tooltip"
                                        data-tip="Delete">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                No buildings found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/30">
            {{ $clients->links(data: ['scrollTo' => false]) }}
        </div>
    </div>

    <livewire:pages::admin.client.edit />
</div>