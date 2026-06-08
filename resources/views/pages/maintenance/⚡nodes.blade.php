<?php

use Livewire\Component;
use App\Models\BEMS\Sensor;
use App\Models\BEMS\PendingNode;
use App\Models\BEMS\Node;
use App\Models\BEMS\Client;
use App\Models\BEMS\Room;
use App\Services\MqttService;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component {
    use WithPagination, Toast, AuthorizesRequests;

    public string $activeTab = 'pending';

    public bool $configMode = false;
    public ?int $configuringNodeId = null;
    public array $configuringNode = [];
    public ?int $selectedClient = null;
    public ?int $selectedRoom = null;
    public string $nodeName = '';

    public string $sensorType = '';
    public string $measurementType = 'temperature';
    public string $unit = 'celsius';
    public string $visualizationType = 'line';
    public array $configuredSensors = [];

    public function render()
    {
        $user = auth()->user();
        $clientId = $user->getTenantClientId();

        $sensors = collect();
        if ($this->activeTab === 'registered') {
            $query = Sensor::with('room.client');
            
            if ($clientId) {
                $query->whereHas('room', fn ($q) => $q->where('client_id', $clientId));
            }
            
            $sensors = $query->paginate(15);
        }

        $pendingNodes = PendingNode::pending()->latest()->get();
        $pendingCount = $pendingNodes->count();

        $clients = Client::orderBy('name')->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]);

        $rooms = $this->selectedClient
            ? Room::withoutGlobalScopes()
                ->where('client_id', $this->selectedClient)
                ->orderBy('name')
                ->get()
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
            : collect();

        return $this->view([
            'sensors' => $sensors,
            'pendingNodes' => $pendingNodes,
            'pendingCount' => $pendingCount,
            'clients' => $clients,
            'rooms' => $rooms,
        ]);
    }

    public function reprovision($id)
    {
        $sensor = Sensor::findOrFail($id);
        $sensor->refreshMqttTopics();
        $this->success("MQTT topics regenerated for {$sensor->mac_address}. Waiting for device...");
    }

    public function deleteNode($id)
    {
        $this->authorize('register nodes');

        $sensor = Sensor::findOrFail($id);
        $macAddress = $sensor->mac_address;
        
        $node = Node::where('device_id', $macAddress)->first();
        
        if ($node) {
            Sensor::where('node_id', $node->id)->delete();
            $node->delete();
        } else {
            Sensor::where('mac_address', $macAddress)->delete();
        }
        
        $this->success("Node {$macAddress} dan seluruh sensor yang terkait telah dihapus.");
    }

    public function configure(int $id): void
    {
        $this->authorize('register nodes');

        $pending = PendingNode::find($id);
        if (! $pending) {
            $this->error('Node not found in queue.');
            return;
        }

        $this->configuringNodeId = $pending->id;
        $this->configuringNode = [
            'mac'      => $pending->mac_address,
            'chip'     => $pending->chip_type ?? 'Unknown',
            'firmware' => $pending->firmware_ver ?? 'Unknown',
            'time'     => $pending->created_at->diffForHumans(),
        ];
        $this->configMode = true;

        $this->selectedClient    = null;
        $this->selectedRoom      = null;
        $this->nodeName          = '';
        $this->configuredSensors = [];
        $this->resetSensorForm();
    }

    private function resetSensorForm(): void
    {
        $this->sensorType        = '';
        $this->measurementType   = 'temperature';
        $this->unit              = 'celsius';
        $this->visualizationType = 'line';
    }

    public function updatedMeasurementType($value): void
    {
        $this->unit = match ($value) {
            'temperature' => 'celsius',
            'humidity', 'percentage' => 'percent',
            'power'   => 'watt',
            'voltage' => 'volt',
            default   => '',
        };
    }

    public function updatedSelectedClient(): void
    {
        $this->selectedRoom = null;
    }

    public function addSensor(): void
    {
        $this->validate([
            'sensorType'      => 'required|string',
            'measurementType' => 'required|string',
            'unit'            => 'required|string',
            'visualizationType' => 'required|string',
        ], [
            'sensorType.required' => 'Pilih tipe sensor.',
        ]);

        $this->configuredSensors[] = [
            'type'               => $this->sensorType,
            'measurement_type'   => $this->measurementType,
            'unit'               => $this->unit,
            'visualization_type' => $this->visualizationType,
        ];

        $this->resetSensorForm();
        $this->success('Sensor ditambahkan ke daftar.');
    }

    public function removeSensor(int $index): void
    {
        if (isset($this->configuredSensors[$index])) {
            unset($this->configuredSensors[$index]);
            $this->configuredSensors = array_values($this->configuredSensors);
        }
    }

    public function backToList(): void
    {
        $this->configMode        = false;
        $this->configuringNodeId = null;
        $this->configuringNode   = [];
        $this->configuredSensors = [];
    }

    public function saveNode(): void
    {
        $this->authorize('register nodes');

        $this->validate([
            'selectedRoom' => 'required|exists:rooms,id',
        ], [
            'selectedRoom.required' => 'Pilih ruangan terlebih dahulu.',
        ]);

        if (empty($this->configuredSensors)) {
            $this->error('Tambahkan minimal satu sensor sebelum menyimpan.');
            return;
        }

        $pending = PendingNode::find($this->configuringNodeId);
        if (! $pending) {
            $this->error('Pending node no longer exists.');
            return;
        }

        $node = Node::create([
            'room_id'    => $this->selectedRoom,
            'device_id'  => $pending->mac_address,
            'name'       => $this->nodeName ?: "Node {$pending->mac_address}",
            'status'     => 'offline',
            'meta'       => [
                'chip_type'    => $pending->chip_type,
                'firmware_ver' => $pending->firmware_ver,
            ],
        ]);

        foreach ($this->configuredSensors as $sensorData) {
            $sensor = Sensor::create([
                'room_id'            => $node->room_id,
                'node_id'            => $node->id,
                'mac_address'        => $node->device_id,
                'type'               => $sensorData['type'],
                'measurement_type'   => $sensorData['measurement_type'],
                'unit'               => $sensorData['unit'],
                'visualization_type' => $sensorData['visualization_type'],
                'is_enabled'         => true,
            ]);

            $sensor->refreshMqttTopics();
        }

        $pending->update(['status' => 'approved']);

        $mqttService = app(MqttService::class);
        $published   = $mqttService->publishProvisioningConfig($node);

        $this->configMode        = false;
        $this->configuringNodeId = null;
        $this->configuringNode   = [];
        $this->configuredSensors = [];

        $mqttMsg = $published
            ? 'Konfigurasi MQTT telah dikirim ke perangkat.'
            : 'Node tersimpan. Konfigurasi MQTT akan dikirim saat broker tersedia.';

        $this->success("Node {$node->device_id} berhasil didaftarkan dengan sensor! {$mqttMsg}");
    }

    public function rejectNode(int $id): void
    {
        $this->authorize('register nodes');

        $pending = PendingNode::find($id);
        if ($pending) {
            $pending->update(['status' => 'rejected']);
            $this->success("Node {$pending->mac_address} ditolak.");
        }
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden" wire:poll.5s>
    <header class="mb-gutter flex justify-between items-end">
        <div>
            <h1 class="font-h1 text-h1 text-slate-800 dark:text-slate-100 mb-1">Node Registration</h1>
            <p class="font-body-md text-body-md text-slate-500 dark:text-slate-400">Register new nodes or manage existing sensors.</p>
        </div>
    </header>

    {{-- Tabs --}}
    <div class="flex gap-6 border-b border-slate-200 dark:border-slate-800 mb-6">
        <button wire:click="$set('activeTab', 'pending')"
            class="pb-3 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'pending' ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500' : 'text-slate-500 dark:text-slate-400 border-transparent hover:text-slate-800 dark:hover:text-slate-200' }}">
            Perangkat Baru 
            @if($pendingCount > 0)
                <span class="ml-2 bg-rose-500 text-white text-[10px] px-2 py-0.5 rounded-full">{{ $pendingCount }}</span>
            @endif
        </button>
        <button wire:click="$set('activeTab', 'registered')"
            class="pb-3 text-sm font-medium transition-colors border-b-2 {{ $activeTab === 'registered' ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500' : 'text-slate-500 dark:text-slate-400 border-transparent hover:text-slate-800 dark:hover:text-slate-200' }}">
            Sensor Terdaftar
        </button>
    </div>

    {{-- Tab Contents --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        
        @if($activeTab === 'pending')
            
            @if($configMode)
                {{-- ─── CONFIGURATION FORM ─── --}}
                <div class="space-y-6 p-6">
                    <div class="flex justify-between items-center pb-4 border-b border-outline-variant/20">
                        <h2 class="text-lg font-semibold text-on-background">Konfigurasi Perangkat Baru</h2>
                        <button wire:click="backToList" class="text-on-surface-variant hover:text-on-surface p-1">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    {{-- Device Info (Read-Only) --}}
                    <div class="bg-surface-container-highest rounded-xl p-5 border border-outline-variant/30">
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-widest mb-4 font-bold">Identitas Perangkat</p>
                        <div class="grid grid-cols-4 gap-4">
                            <div>
                                <span class="text-[10px] text-on-surface-variant uppercase tracking-wider">MAC Address</span>
                                <p class="text-on-surface font-mono text-sm mt-1">{{ $configuringNode['mac'] ?? '—' }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] text-on-surface-variant uppercase tracking-wider">Chip</span>
                                <p class="text-on-surface text-sm mt-1">{{ $configuringNode['chip'] ?? '—' }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] text-on-surface-variant uppercase tracking-wider">Firmware</span>
                                <p class="text-on-surface text-sm mt-1">{{ $configuringNode['firmware'] ?? '—' }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] text-on-surface-variant uppercase tracking-wider">Ditemukan</span>
                                <p class="text-on-surface text-sm mt-1">{{ $configuringNode['time'] ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Node Name --}}
                    <div>
                        <label class="text-xs text-on-surface-variant uppercase tracking-wider font-medium block mb-2">Nama Node (Opsional)</label>
                        <input wire:model="nodeName" type="text" placeholder="Contoh: Node Server Utama"
                            class="w-full bg-surface-container-highest border border-outline-variant/50 rounded-lg px-4 py-2.5 text-sm text-on-surface placeholder-on-surface-variant focus:outline-none focus:border-primary transition-colors" />
                    </div>

                    {{-- Location Selection --}}
                    <div class="space-y-4">
                        <p class="text-[10px] text-on-surface-variant uppercase tracking-widest font-bold">Penempatan Lokasi</p>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-on-surface-variant uppercase tracking-wider font-medium block mb-2">Pilih Gedung / Klien</label>
                                <select wire:model.live="selectedClient"
                                    class="w-full bg-surface-container-highest border border-outline-variant/50 rounded-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary transition-colors">
                                    <option value="">— Pilih Gedung —</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client['id'] }}">{{ $client['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-on-surface-variant uppercase tracking-wider font-medium block mb-2">Pilih Ruangan</label>
                                <select wire:model.live="selectedRoom"
                                    class="w-full bg-surface-container-highest border border-outline-variant/50 rounded-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary transition-colors disabled:opacity-40"
                                    @disabled(!$selectedClient)>
                                    <option value="">— Pilih Ruangan —</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room['id'] }}">{{ $room['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- ═══ SENSOR CONFIGURATION ═══ --}}
                    <div class="space-y-4 pt-4 border-t border-outline-variant/20">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] text-primary uppercase tracking-widest font-bold">Konfigurasi Sensor</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Sensor Type --}}
                            <div>
                                <label class="text-xs text-on-surface-variant uppercase tracking-wider font-medium block mb-2">Tipe Sensor</label>
                                <select wire:model="sensorType"
                                    class="w-full bg-surface-container-highest border border-outline-variant/50 rounded-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary transition-colors">
                                    <option value="">— Pilih Tipe —</option>
                                    <option value="Environmental">Environmental (Temp/Humidity)</option>
                                    <option value="Power">Power Consumption Monitor</option>
                                    <option value="Motion">PIR Motion Detector</option>
                                    <option value="Air Quality">Air Quality (VOC/CO2)</option>
                                    <option value="Door">Contact Sensor</option>
                                </select>
                            </div>

                            {{-- Measurement --}}
                            <div>
                                <label class="text-xs text-on-surface-variant uppercase tracking-wider font-medium block mb-2">Pengukuran</label>
                                <select wire:model.live="measurementType"
                                    class="w-full bg-surface-container-highest border border-outline-variant/50 rounded-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary transition-colors">
                                    <option value="temperature">Temperature</option>
                                    <option value="humidity">Humidity</option>
                                    <option value="power">Power / Energy</option>
                                    <option value="voltage">Voltage</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Unit --}}
                            <div>
                                <label class="text-xs text-on-surface-variant uppercase tracking-wider font-medium block mb-2">Satuan</label>
                                <select wire:model="unit"
                                    class="w-full bg-surface-container-highest border border-outline-variant/50 rounded-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary transition-colors">
                                    @switch($measurementType)
                                        @case('temperature')
                                            <option value="celsius">Celsius (°C)</option>
                                            <option value="fahrenheit">Fahrenheit (°F)</option>
                                            @break
                                        @case('humidity')
                                        @case('percentage')
                                            <option value="percent">Percent (%)</option>
                                            @break
                                        @case('power')
                                            <option value="watt">Watt (W)</option>
                                            @break
                                        @case('voltage')
                                            <option value="volt">Volt (V)</option>
                                            @break
                                    @endswitch
                                </select>
                            </div>

                            {{-- Visualization --}}
                            <div>
                                <label class="text-xs text-on-surface-variant uppercase tracking-wider font-medium block mb-2">Visualisasi</label>
                                <select wire:model="visualizationType"
                                    class="w-full bg-surface-container-highest border border-outline-variant/50 rounded-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary transition-colors">
                                    <option value="line">Line Chart</option>
                                    <option value="gauge">Gauge</option>
                                    <option value="widget">Stat Widget</option>
                                    <option value="light">Light Control</option>
                                    <option value="ac">AC Control</option>
                                </select>
                            </div>
                        </div>

                        <button wire:click="addSensor" type="button"
                            class="w-full py-2.5 mt-2 bg-primary/10 border border-primary/30 text-primary text-sm font-medium rounded-lg hover:bg-primary/20 hover:border-primary/50 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">add_circle</span>
                            Tambah Sensor ke Daftar
                        </button>
                    </div>

                    {{-- ═══ LIST OF CONFIGURED SENSORS ═══ --}}
                    @if(count($configuredSensors) > 0)
                        <div class="space-y-3 pt-4">
                            <p class="text-[10px] text-emerald-500 uppercase tracking-widest font-bold flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">checklist</span>
                                Sensor Terdaftar ({{ count($configuredSensors) }})
                            </p>
                            @foreach($configuredSensors as $index => $s)
                                <div class="bg-surface-container-highest rounded-lg p-3 border border-outline-variant/30 flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-primary/10 border border-primary/20 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[20px] text-primary">
                                                {{ match($s['visualization_type']) {
                                                    'line'   => 'show_chart',
                                                    'gauge'  => 'speed',
                                                    'light'  => 'lightbulb',
                                                    'ac'     => 'mode_fan',
                                                    default  => 'dashboard'
                                                } }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="text-on-surface text-sm font-medium">{{ ucfirst($s['measurement_type']) }}</p>
                                            <p class="text-[11px] text-on-surface-variant">
                                                {{ $s['type'] }} · {{ $s['unit'] ? strtoupper($s['unit']) : '—' }} · {{ ucfirst(str_replace('_', ' ', $s['visualization_type'])) }}
                                            </p>
                                        </div>
                                    </div>
                                    <button wire:click="removeSensor({{ $index }})"
                                        class="text-on-surface-variant hover:text-rose-500 transition-colors p-2 rounded hover:bg-rose-500/10"
                                        title="Hapus sensor">
                                        <span class="material-symbols-outlined text-[18px]">close</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-6 border-t border-outline-variant/20">
                        <button wire:click="backToList"
                            class="px-5 py-2.5 border border-outline-variant/50 text-on-surface hover:bg-surface-variant rounded-lg text-sm font-medium transition-colors">
                            Batalkan
                        </button>
                        <button wire:click="saveNode" wire:loading.attr="disabled"
                            class="px-5 py-2.5 bg-primary hover:bg-primary/90 text-on-primary rounded-lg text-sm font-medium transition-colors flex items-center gap-2 disabled:opacity-50"
                            @disabled(count($configuredSensors) === 0)>
                            <span wire:loading wire:target="saveNode" class="loading loading-spinner loading-xs"></span>
                            <span class="material-symbols-outlined text-sm">check_circle</span>
                            Setujui & Aktifkan
                        </button>
                    </div>
                </div>
            @else
                {{-- ─── PENDING NODES LIST ─── --}}
                @forelse($pendingNodes as $node)
                    <div wire:key="pending-{{ $node->id }}" class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <div class="flex-1 flex flex-col justify-center pl-6 py-4">
                            <span class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">{{ $node->mac_address }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $node->chip_type ?? 'Unknown Chip' }} · {{ $node->firmware_ver ?? '?' }}</span>
                        </div>
                        <div class="flex items-center pr-6 gap-3">
                            <button wire:click="configure({{ $node->id }})"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">settings</span>
                                Konfigurasi
                            </button>
                            <button wire:click="rejectNode({{ $node->id }})"
                                wire:confirm="Tolak perangkat ini dari antrean?"
                                class="px-3 py-2 text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-colors" title="Tolak">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-20 text-center px-6">
                        <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-4 border border-slate-200 dark:border-slate-700">
                            <span class="material-symbols-outlined text-3xl text-slate-400 dark:text-slate-500">search</span>
                        </div>
                        <p class="text-slate-800 dark:text-slate-200 font-medium">Tidak ada permintaan baru</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-sm">Perangkat baru akan muncul di sini saat terdeteksi oleh sistem.</p>
                    </div>
                @endforelse
            @endif

        @else
            {{-- ─── REGISTERED SENSORS TAB ─── --}}
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30">
                        <th class="py-3 px-unit-lg font-label-sm text-label-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider">MAC Address</th>
                        <th class="py-3 px-unit-md font-label-sm text-label-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider">Room</th>
                        <th class="py-3 px-unit-md font-label-sm text-label-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-unit-md font-label-sm text-label-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider">MQTT Topic</th>
                        <th class="py-3 px-unit-lg font-label-sm text-label-sm text-slate-500 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="font-body-sm text-body-sm divide-y divide-slate-100 dark:divide-slate-800/50">
                    @forelse($sensors as $sensor)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3 px-unit-lg font-mono text-xs text-slate-800 dark:text-slate-200">{{ $sensor->mac_address }}</td>
                            <td class="py-3 px-unit-md text-slate-600 dark:text-slate-400">{{ $sensor->room->name ?? 'N/A' }}</td>
                            <td class="py-3 px-unit-md">
                                @php
                                    $pConfig = match($sensor->provision_status ?? 'pending') {
                                        'provisioned' => ['text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-500/20 border-emerald-200 dark:border-emerald-500/20', 'Provisioned', 'check_circle'],
                                        'waiting_provision' => ['text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-500/20 border-amber-200 dark:border-amber-500/20 animate-pulse', 'Waiting', 'hourglass_top'],
                                        default => ['text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700', 'Pending', 'pending'],
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border font-label-sm text-label-sm {{ $pConfig[0] }}">
                                    <span class="material-symbols-outlined text-[14px]">{{ $pConfig[2] }}</span>
                                    {{ $pConfig[1] }}
                                </span>
                            </td>
                            <td class="py-3 px-unit-md">
                                <code class="text-[10px] text-slate-500 dark:text-slate-400 font-mono break-all">{{ $sensor->mqtt_pub_topic ?? '—' }}</code>
                            </td>
                            <td class="py-3 px-unit-lg text-right">
                                <button wire:click="reprovision({{ $sensor->id }})" wire:confirm="Regenerate MQTT topics?"
                                    class="p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                    title="Re-provision">
                                    <span class="material-symbols-outlined text-[18px]">refresh</span>
                                </button>
                                <button wire:click="deleteNode({{ $sensor->id }})" wire:confirm="Hapus node {{ $sensor->mac_address }} beserta semua sensornya secara permanen?"
                                    class="p-1.5 rounded hover:bg-rose-50 dark:hover:bg-rose-500/10 text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors ml-1"
                                    title="Hapus Node">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-slate-500 dark:text-slate-400">No sensors registered.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if(method_exists($sensors, 'links'))
            <div class="px-unit-lg py-unit-sm border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30">
                {{ $sensors->links() }}
            </div>
            @endif
        @endif
    </div>
</div>
