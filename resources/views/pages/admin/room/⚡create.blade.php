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

    public $client_id = '';
    public $name = '';
    public $floor = '';
    public $category = '';

    public function rules()
    {
        return [
            'client_id' => 'required|exists:bems_clients,id',
            'name' => 'required|string|max:255',
            'floor' => 'nullable|string|max:255',
            'category' => 'required|string',
        ];
    }

    #[On('show-create-room')]
    public function showModal()
    {
        $this->reset(['client_id', 'name', 'floor', 'category']);
        $this->show = true;
    }

    public function save()
    {
        $this->validate();

        $icons = [
            'office' => 'meeting_room',
            'meeting' => 'groups',
            'server' => 'dns',
            'storage' => 'inventory_2',
            'common' => 'chair',
        ];

        Room::create([
            'client_id' => $this->client_id,
            'name' => $this->name,
            'floor' => $this->floor,
            'category' => $this->category,
            'icon' => $icons[$this->category] ?? 'door_open',
            'status' => 'OPERATIONAL',
            'total_nodes' => 0,
        ]);

        $this->show = false;
        $this->success('Room created successfully.');
        $this->dispatch('refreshIndexRoom');
    }
};
?>

<div>
    <x-drawer wire:model="show" title="Add New Room" right class="w-full sm:w-[440px] bg-slate-900" separator>
        
        <div class="mb-6">
            <p class="font-body-sm text-body-sm text-slate-400">Configure spatial metadata and assign sensor nodes.</p>
        </div>

        <x-form wire:submit="save" class="space-y-6">
            
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

            <x-slot name="actions">
                <x-button label="Cancel" @click="$wire.show = false" />
                <x-button label="Save Room" class="btn-primary" type="submit" spinner="save" icon="o-check" />
            </x-slot>

        </x-form>

    </x-drawer>
</div>
