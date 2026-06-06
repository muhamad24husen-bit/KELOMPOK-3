<?php

use Livewire\Component;
use App\Models\BEMS\Client;
use App\Models\BEMS\Room;
use Mary\Traits\Toast;
use Livewire\Attributes\On;

new class extends Component
{
    use Toast;

    public bool $show = false;

    public $room_id;
    public $client_id = '';
    public $name = '';
    public $floor = '';
    public $category = '';
    public $status = '';

    public function rules()
    {
        return [
            'client_id' => 'required|exists:bems_clients,id',
            'name' => 'required|string|max:255',
            'floor' => 'nullable|string|max:255',
            'category' => 'required|string',
            'status' => 'required|string',
        ];
    }

    #[On('edit-room')]
    public function loadRoom($id)
    {
        $room = Room::findOrFail($id);

        $this->room_id = $room->id;
        $this->client_id = $room->client_id;
        $this->name = $room->name;
        $this->floor = $room->floor;
        $this->category = $room->category ?? 'office';
        $this->status = $room->status ?? 'OPERATIONAL';

        $this->show = true;
    }

    public function update()
    {
        $this->validate();

        $room = Room::findOrFail($this->room_id);

        $icons = [
            'office' => 'meeting_room',
            'meeting' => 'groups',
            'server' => 'dns',
            'storage' => 'inventory_2',
            'common' => 'chair',
        ];

        $room->update([
            'client_id' => $this->client_id,
            'name' => $this->name,
            'floor' => $this->floor,
            'category' => $this->category,
            'icon' => $icons[$this->category] ?? $room->icon ?? 'door_open',
            'status' => $this->status,
        ]);

        $this->show = false;
        $this->success('Room updated successfully.');
        $this->dispatch('refreshIndexRoom');
    }
};
?>

<div>
    <x-drawer wire:model="show" title="Edit Room" right class="w-full sm:w-[440px] bg-slate-900" separator>

        <div class="mb-6">
            <p class="font-body-sm text-body-sm text-slate-400">Update room configuration and metadata.</p>
        </div>

        <x-form wire:submit="update" class="space-y-6">

            {{-- Building Select --}}
            <x-select
                label="Select Building"
                wire:model="client_id"
                :options="\App\Models\BEMS\Client::orderBy('name')->get()"
                placeholder="Choose a facility..."
                required
            />

            {{-- Room Name --}}
            <x-input label="Room Name" wire:model="name" placeholder="e.g., Conference Room D" required />

            {{-- 2-Column Grid --}}
            <div class="grid grid-cols-2 gap-4">
                {{-- Floor Level --}}
                <x-input label="Floor Level" wire:model="floor" placeholder="e.g., Lantai 4" />

                {{-- Room Category --}}
                <x-select
                    label="Room Category"
                    wire:model="category"
                    :options="[
                        ['id' => 'office', 'name' => 'Office / Workspace'],
                        ['id' => 'meeting', 'name' => 'Meeting Room'],
                        ['id' => 'server', 'name' => 'Server Room'],
                        ['id' => 'storage', 'name' => 'Storage / Utility'],
                        ['id' => 'common', 'name' => 'Common Area'],
                    ]"
                    placeholder="Select category..."
                    required
                />
            </div>

            {{-- Status --}}
            <x-select
                label="Status"
                wire:model="status"
                :options="[
                    ['id' => 'OPERATIONAL', 'name' => 'Operational'],
                    ['id' => 'MAINTENANCE', 'name' => 'Maintenance'],
                    ['id' => 'CRITICAL', 'name' => 'Critical'],
                ]"
                required
            />

            <x-slot name="actions">
                <x-button label="Cancel" @click="$wire.show = false" />
                <x-button label="Save Changes" class="btn-primary" type="submit" spinner="update" icon="o-check" />
            </x-slot>

        </x-form>

    </x-drawer>
</div>
