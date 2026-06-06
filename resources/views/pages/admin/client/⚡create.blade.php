<?php

use Livewire\Component;
use App\Models\BEMS\Client;
use Mary\Traits\Toast;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\WithFileUploads;

new class extends Component
{
    use Toast, WithFileUploads;

    public $createDrawer = false;
    
    // Form fields
    public $name;
    public $gedung; // Primary Address
    public $kelas; // City/Zone
    public $total_rooms;
    public $thumbnail;
    public $code;
    public $expirity;
    
    public $search = '';

    public function openDrawer()
    {
        $this->createDrawer = true;
    }

    public function saveClient()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'gedung' => 'nullable|string',
            'kelas' => 'nullable|string',
            'total_rooms' => 'nullable|integer|min:0',
            'thumbnail' => 'nullable|image|max:5120', // 5MB max
            'expirity' => 'nullable|date',
            'code' => 'nullable|string|unique:bems_clients,code',
        ]);

        // Auto-generate code if empty
        if (!$this->code) {
            $this->code = strtoupper(substr($this->name, 0, 3)) . rand(1000, 9999);
            // Ensure unique
            while (Client::where('code', $this->code)->exists()) {
                $this->code = strtoupper(substr($this->name, 0, 3)) . rand(1000, 9999);
            }
        }

        // Handle thumbnail upload
        $thumbnailPath = null;
        if ($this->thumbnail) {
            $thumbnailPath = $this->thumbnail->store('thumbnails', 'public');
        }

        // Create or find user for this client
        $user = User::firstOrCreate(
            ['email' => strtolower($this->code) . "@bems.id"],
            [
                'name' => $this->name,
                'password' => Hash::make($this->code . "1809##")
            ]
        );

        // Create the client
        Client::create([
            'code' => $this->code,
            'name' => $this->name,
            'user_id' => $user->id,
            'expirity' => $this->expirity ?? now()->addYear(),
            'kelas' => $this->kelas,
            'gedung' => $this->gedung,
            'total_rooms' => $this->total_rooms,
            'thumbnail' => $thumbnailPath,
        ]);

        $this->success('New building registered successfully!');
        
        $this->reset(['name', 'gedung', 'kelas', 'total_rooms', 'thumbnail', 'code', 'expirity']);
        $this->createDrawer = false;
        $this->dispatch('refreshIndexClient');
    }
};
?>

<div>
    {{-- TRIGGER BUTTON --}}
    <button wire:click="openDrawer" class="flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all active:scale-[0.98] shadow-lg shadow-blue-900/20">
        <span class="material-symbols-outlined text-[20px]">add</span>
        <span>Add Building</span>
    </button>

    {{-- DRAWER --}}
    <x-drawer wire:model="createDrawer" title="Add New Building" right class="w-full sm:w-[440px] bg-slate-900" separator>
        
        <x-form wire:submit="saveClient" class="space-y-6">
            
            {{-- Building Name --}}
            <x-input label="Building Name" wire:model="name" placeholder="e.g. Horizon Tower" required />

            {{-- Primary Address --}}
            <x-textarea label="Primary Address" wire:model="gedung" placeholder="Enter full street address..." rows="3" />

            {{-- City/Zone: input teks bebas (lebih fleksibel untuk berbagai kota Indonesia) --}}
            <x-input 
                label="Kota / Zona"
                wire:model="kelas" 
                placeholder="Contoh: Jakarta Selatan, Surabaya, Bandung..."
                icon="o-map-pin"
            />

            {{-- Total Rooms --}}
            <x-input label="Total Rooms" wire:model="total_rooms" type="number" placeholder="0" />

            {{-- Building Thumbnail --}}
            <x-file label="Building Thumbnail" wire:model="thumbnail" accept="image/*" />
            
            @if ($thumbnail)
                <div class="mt-2 relative w-32 h-32 rounded-lg overflow-hidden border border-slate-700">
                    <img src="{{ $thumbnail->temporaryUrl() }}" class="w-full h-full object-cover">
                </div>
            @endif

            {{-- Advanced --}}
            <div class="pt-4 border-t border-slate-800 space-y-4">
                <span class="text-sm font-medium text-slate-400">Advanced Settings</span>
                <x-input label="Building Code" wire:model="code" placeholder="Auto-generated if empty" />
                <x-input label="Expiry Date" wire:model="expirity" type="date" />
            </div>

            <x-slot name="actions">
                <x-button label="Cancel" @click="$wire.createDrawer = false" />
                <x-button label="Register Building" class="btn-primary" type="submit" spinner="saveClient" />
            </x-slot>

        </x-form>

    </x-drawer>
</div>