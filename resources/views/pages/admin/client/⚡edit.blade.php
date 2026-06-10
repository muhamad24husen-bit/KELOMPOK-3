<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\BEMS\Client;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;

new class extends Component
{
    use Toast, WithFileUploads;

    public $clientEditDrawer = false;
    
    public $clientId;
    public $code;
    public $name;
    public $gedung; // Primary Address
    public $kelas; // City/Zone
    public $total_rooms;
    public $thumbnail;
    public $existingThumbnail;
    public $expirity;
    public $removeThumbnail = false;

    #[On('enableEditClient')]
    public function enableEditClient($clientId)
    {
        $this->clientId = $clientId;
        $client = Client::find($clientId);
        
        $this->code = $client->code;
        $this->name = $client->name;
        $this->gedung = $client->gedung;
        $this->kelas = $client->kelas;
        $this->total_rooms = $client->total_rooms;
        $this->existingThumbnail = $client->thumbnail;
        $this->expirity = $client->expirity;
        $this->removeThumbnail = false;
        
        $this->clientEditDrawer = true;
    }

    public function removeExistingThumbnail()
    {
        $this->existingThumbnail = null;
        $this->removeThumbnail = true;
    }

    public function updateClient()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'gedung' => 'nullable|string',
            'kelas' => 'nullable|string',
            'total_rooms' => 'nullable|integer|min:0',
            'thumbnail' => 'nullable|image|max:5120',
            'expirity' => 'nullable|date',
            'code' => 'required|string|unique:bems_clients,code,' . $this->clientId,
        ]);

        $client = Client::find($this->clientId);
        
        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'gedung' => $this->gedung,
            'kelas' => $this->kelas,
            'total_rooms' => $this->total_rooms,
            'expirity' => $this->expirity,
        ];

        if ($this->thumbnail) {
            $data['thumbnail'] = $this->thumbnail->store('thumbnails', 'public');
        } elseif ($this->removeThumbnail) {
            $data['thumbnail'] = null;
        }

        $client->update($data);

        $this->success('Building information updated successfully!');
        $this->clientEditDrawer = false;
        $this->dispatch('refreshIndexClient');
    }
};
?>

<div>
    <x-drawer wire:model="clientEditDrawer" title="Edit Building" right class="w-full sm:w-[440px] bg-slate-900" separator>
        
        <x-form wire:submit="updateClient" class="space-y-6">
            
            <x-input label="Building Name" wire:model="name" required />

            <x-textarea label="Primary Address" wire:model="gedung" rows="3" />

            {{-- City/Zone: input teks bebas --}}
            <x-input 
                label="Kota / Zona"
                wire:model="kelas" 
                placeholder="Contoh: Jakarta Selatan, Surabaya, Bandung..."
                icon="o-map-pin"
            />

            <x-input label="Total Rooms" wire:model="total_rooms" type="number" />

            <x-file label="Building Thumbnail (512x512 px, Maks. 5MB)" wire:model="thumbnail" accept="image/*" />
            
            @if ($existingThumbnail && !$thumbnail)
                <div class="mt-2 relative w-32 h-32 rounded-lg overflow-hidden border border-slate-700 group">
                    <img src="{{ asset('storage/' . $existingThumbnail) }}" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <x-button type="button" icon="o-trash" wire:click="removeExistingThumbnail" class="btn-error btn-sm text-white" tooltip="Remove thumbnail" />
                    </div>
                </div>
            @endif

            @if ($thumbnail)
                <div class="mt-2 relative w-32 h-32 rounded-lg overflow-hidden border border-slate-700">
                    <img src="{{ $thumbnail->temporaryUrl() }}" class="w-full h-full object-cover">
                </div>
            @endif

            <div class="pt-4 border-t border-slate-800 space-y-4">
                <x-input label="Building Code" wire:model="code" required />
                <x-input label="Expiry Date" wire:model="expirity" type="date" />
            </div>

            <x-slot name="actions">
                <x-button label="Cancel" @click="$wire.clientEditDrawer = false" />
                <x-button label="Save Changes" class="btn-primary" type="submit" spinner="updateClient" />
            </x-slot>

        </x-form>

    </x-drawer>
</div>