<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BEMS\PendingNode;
use App\Models\BEMS\Node;
use App\Models\BEMS\Client;
use App\Models\BEMS\Room;
use App\Models\BEMS\Sensor;
use App\Services\MqttService;
use Mary\Traits\Toast;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class NodeDiscoveryDrawer extends Component
{
    use Toast;
    use AuthorizesRequests;

    // ── Drawer State ───────────────────────────────────────────
    public bool $drawerOpen = false;
    public bool $configMode = false;

    // ── Configuration Form ─────────────────────────────────────
    public ?int $configuringNodeId = null;
    public array $configuringNode = [];
    public ?int $selectedClient = null;
    public ?int $selectedRoom = null;
    public string $nodeName = '';

    // ── Sensor Form (manual, one-by-one) ──────────────────────
    public string $sensorType = '';
    public string $measurementType = 'temperature';
    public string $unit = 'celsius';
    public string $visualizationType = 'line';
    public array $configuredSensors = [];

    // ── Render ─────────────────────────────────────────────────

    public function render()
    {
        $pendingNodes = PendingNode::pending()
            ->latest()
            ->get();

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

        return view('livewire.node-discovery-drawer', [
            'pendingNodes' => $pendingNodes,
            'pendingCount' => $pendingCount,
            'clients'      => $clients,
            'rooms'        => $rooms,
        ]);
    }

    // ── Actions ────────────────────────────────────────────────

    /**
     * Open the configuration form for a specific pending node.
     */
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

        // Reset form
        $this->selectedClient    = null;
        $this->selectedRoom      = null;
        $this->nodeName          = '';
        $this->configuredSensors = [];
        $this->resetSensorForm();
    }

    /**
     * Reset the individual sensor form fields.
     */
    private function resetSensorForm(): void
    {
        $this->sensorType        = '';
        $this->measurementType   = 'temperature';
        $this->unit              = 'celsius';
        $this->visualizationType = 'line';
    }

    /**
     * Auto-update unit when measurement type changes.
     */
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

    /**
     * Reset rooms when client changes.
     */
    public function updatedSelectedClient(): void
    {
        $this->selectedRoom = null;
    }

    /**
     * Add a sensor to the configured sensors list.
     */
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

    /**
     * Remove a sensor from the configured list.
     */
    public function removeSensor(int $index): void
    {
        if (isset($this->configuredSensors[$index])) {
            unset($this->configuredSensors[$index]);
            $this->configuredSensors = array_values($this->configuredSensors);
        }
    }

    /**
     * Go back to the pending nodes list.
     */
    public function backToList(): void
    {
        $this->configMode        = false;
        $this->configuringNodeId = null;
        $this->configuringNode   = [];
        $this->configuredSensors = [];
    }

    /**
     * Approve the pending node: move to `nodes` table, create sensors, publish MQTT config.
     */
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

        // 1. Create the Node record
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

        // 2. Create Sensors from the manually configured list
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

        // 3. Mark pending node as approved
        $pending->update(['status' => 'approved']);

        // 4. Publish provisioning config via MQTT
        $mqttService = app(MqttService::class);
        $published   = $mqttService->publishProvisioningConfig($node);

        // 5. Reset form & close config
        $this->configMode        = false;
        $this->configuringNodeId = null;
        $this->configuringNode   = [];
        $this->configuredSensors = [];

        $mqttMsg = $published
            ? 'Konfigurasi MQTT telah dikirim ke perangkat.'
            : 'Node tersimpan. Konfigurasi MQTT akan dikirim saat broker tersedia.';

        $sensorCount = count($this->configuredSensors) ?: 'beberapa';
        $this->success("Node {$node->device_id} berhasil didaftarkan dengan sensor! {$mqttMsg}");
    }

    /**
     * Reject / remove a pending node from the queue.
     */
    public function rejectNode(int $id): void
    {
        $this->authorize('register nodes');

        $pending = PendingNode::find($id);
        if ($pending) {
            $pending->update(['status' => 'rejected']);
            $this->success("Node {$pending->mac_address} ditolak.");
        }
    }
}
