<?php

use Livewire\Component;
use App\Models\BEMS\Room;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

    public string $search = '';
    public string $newRoomName = '';
    public string $newRoomFloor = '';
    public bool $showCreate = false;

    public function render()
    {
        $user = auth()->user();
        $client = $user->bemsClient;

        if ($user->hasRole('super_admin')) {
            $rooms = Room::withoutGlobalScopes()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->withCount('sensors')
                ->paginate(12);
        } else {
            abort_if(!$client, 403, 'No client profile found.');
            $rooms = Room::withoutGlobalScopes()
                ->where('client_id', $client->id)
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->withCount('sensors')
                ->paginate(12);
        }

        return $this->view(['rooms' => $rooms, 'client' => $client]);
    }

    public function createRoom()
    {
        $this->validate([
            'newRoomName'  => 'required|string|max:100',
            'newRoomFloor' => 'nullable|string|max:10',
        ]);

        $client = auth()->user()->bemsClient;
        abort_if(!$client, 403, 'Cannot create room: No client profile found. Super Admins should use the Admin Panel.');

        Room::create([
            'client_id' => $client->id,
            'name'      => $this->newRoomName,
            'floor'     => $this->newRoomFloor ?: '1',
        ]);

        $this->reset(['newRoomName', 'newRoomFloor', 'showCreate']);
        $this->success('Room created successfully.');
    }

    public function deleteRoom($id)
    {
        Room::withoutGlobalScopes()->findOrFail($id)->delete();
        $this->success('Room deleted.');
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden">
    <header class="flex items-end justify-between mb-gutter">
        <div>
            <h1 class="font-h1 text-h1 text-on-background mb-1">Room Management</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Create and manage building rooms.</p>
        </div>
        <button wire:click="$set('showCreate', true)"
            class="flex items-center gap-2 px-4 py-2 bg-primary-container text-on-primary-container rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Add Room
        </button>
    </header>

    <!-- Search -->
    <div class="relative w-full md:w-64 mb-gutter">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-sm">search</span>
        <input wire:model.live="search"
            class="w-full bg-surface-container border border-outline-variant/30 rounded-lg py-2 pl-9 pr-4 text-on-surface placeholder-outline focus:outline-none focus:border-primary font-body-sm text-body-sm"
            placeholder="Search rooms..." type="text" />
    </div>

    <!-- Room Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter mb-gutter">
        @forelse($rooms as $room)
            <div class="bg-surface-container rounded-xl p-unit-lg border border-outline-variant/20 shadow-sm group">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-on-surface-variant">{{ $room->icon ?? 'meeting_room' }}</span>
                        <span class="font-label-md text-label-md text-on-surface">{{ $room->name }}</span>
                    </div>
                    <button wire:click="deleteRoom({{ $room->id }})" wire:confirm="Delete this room?"
                        class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-error-container/20 text-error transition-all"
                        title="Delete">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                    </button>
                </div>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    Floor {{ $room->floor }} · {{ $room->sensors_count }} sensors
                </p>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-surface-container rounded-xl border border-dashed border-outline-variant">
                <span class="material-symbols-outlined text-3xl text-outline mb-2 block">meeting_room</span>
                <p class="text-on-surface-variant">No rooms yet. Click "Add Room" to get started.</p>
            </div>
        @endforelse
    </div>

    <div>{{ $rooms->links() }}</div>

    <!-- Create Modal -->
    <x-modal wire:model="showCreate" title="Add New Room" class="bg-surface-container">
        <x-form wire:submit="createRoom" class="space-y-4">
            <x-input label="Room Name" wire:model="newRoomName" placeholder="e.g., Lobi Utama" required />
            <x-input label="Floor" wire:model="newRoomFloor" placeholder="e.g., 1" />
            <x-slot name="actions">
                <x-button label="Cancel" @click="$wire.showCreate = false" />
                <x-button label="Create Room" class="btn-primary" type="submit" spinner="createRoom" icon="o-check" />
            </x-slot>
        </x-form>
    </x-modal>
</div>
