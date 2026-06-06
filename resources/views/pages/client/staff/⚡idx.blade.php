<?php

use Livewire\Component;
use App\Models\BEMS\Staff;
use App\Models\User;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    use Toast;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $staffRole = 'viewer';
    public bool $showCreate = false;

    public function render()
    {
        $user = auth()->user();
        $client = $user->bemsClient;

        $staffMembers = Staff::with('user')
            ->where('client_id', $client->id)
            ->get();

        return $this->view(['staffMembers' => $staffMembers, 'client' => $client]);
    }

    public function createStaff()
    {
        $this->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'staffRole' => 'required|in:operator,maintenance,viewer',
        ]);

        $client = auth()->user()->bemsClient;

        // Create user
        $newUser = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
            'role'     => $this->staffRole,
        ]);
        $newUser->assignRole($this->staffRole);

        // Create staff link
        Staff::create([
            'user_id'    => $newUser->id,
            'client_id'  => $client->id,
            'staff_role' => $this->staffRole,
        ]);

        $this->reset(['name', 'email', 'password', 'staffRole', 'showCreate']);
        $this->success("Staff member {$newUser->name} added as {$this->staffRole}.");
    }

    public function toggleActive($staffId)
    {
        $staff = Staff::findOrFail($staffId);
        $staff->update(['is_active' => !$staff->is_active]);
        $this->success($staff->is_active ? 'Staff activated.' : 'Staff deactivated.');
    }

    public function removeStaff($staffId)
    {
        $staff = Staff::findOrFail($staffId);
        $staff->user->delete(); // Cascade deletes staff record too
        $this->success('Staff member removed.');
    }
};
?>

<div class="flex-1 p-margin-page overflow-x-hidden">
    <header class="flex items-end justify-between mb-gutter">
        <div>
            <h1 class="font-h1 text-h1 text-on-background mb-1">Staff Management</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Manage operators, maintenance, and viewers.</p>
        </div>
        <button wire:click="$set('showCreate', true)"
            class="flex items-center gap-2 px-4 py-2 bg-primary-container text-on-primary-container rounded-lg font-label-md text-label-md hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-[18px]">person_add</span>
            Add Staff
        </button>
    </header>

    <!-- Staff Table -->
    <div class="bg-surface-container rounded-xl border border-outline-variant/30 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-outline-variant/20 bg-surface-container-high">
                    <th class="py-3 px-unit-lg font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Name</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Email</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Role</th>
                    <th class="py-3 px-unit-md font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                    <th class="py-3 px-unit-lg font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="font-body-sm text-body-sm divide-y divide-outline-variant/10">
                @forelse($staffMembers as $staff)
                    <tr class="hover:bg-surface-container-highest/30 transition-colors">
                        <td class="py-3 px-unit-lg text-on-surface font-medium">{{ $staff->user->name }}</td>
                        <td class="py-3 px-unit-md text-on-surface-variant">{{ $staff->user->email }}</td>
                        <td class="py-3 px-unit-md">
                            @php
                                $rColor = match($staff->staff_role) {
                                    'operator'    => 'text-primary bg-primary-container/20 border-primary-container/50',
                                    'maintenance' => 'text-tertiary bg-tertiary-container/20 border-tertiary-container/50',
                                    'viewer'      => 'text-on-surface-variant bg-surface-variant border-outline-variant/30',
                                    default       => 'text-on-surface-variant',
                                };
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded border font-label-sm text-label-sm {{ $rColor }}">
                                {{ ucfirst($staff->staff_role) }}
                            </span>
                        </td>
                        <td class="py-3 px-unit-md">
                            <span class="inline-flex items-center gap-1.5 font-label-sm text-label-sm {{ $staff->is_active ? 'text-primary' : 'text-error' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $staff->is_active ? 'bg-primary' : 'bg-error' }}"></span>
                                {{ $staff->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-3 px-unit-lg text-right">
                            <div class="flex justify-end gap-1">
                                <button wire:click="toggleActive({{ $staff->id }})"
                                    class="p-1.5 rounded hover:bg-surface-variant text-on-surface-variant transition-colors"
                                    title="{{ $staff->is_active ? 'Deactivate' : 'Activate' }}">
                                    <span class="material-symbols-outlined text-[18px]">{{ $staff->is_active ? 'person_off' : 'person' }}</span>
                                </button>
                                <button wire:click="removeStaff({{ $staff->id }})" wire:confirm="Remove this staff member?"
                                    class="p-1.5 rounded hover:bg-error-container/20 text-error transition-colors"
                                    title="Remove">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12 text-center text-on-surface-variant">No staff members. Click "Add Staff" to invite team members.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Create Staff Modal -->
    <x-modal wire:model="showCreate" title="Add Staff Member" class="bg-surface-container">
        <x-form wire:submit="createStaff" class="space-y-4">
            <x-input label="Full Name" wire:model="name" placeholder="John Doe" required />
            <x-input label="Email" wire:model="email" type="email" placeholder="john@company.com" required />
            <x-input label="Password" wire:model="password" type="password" placeholder="Min 6 characters" required />
            <x-select label="Role" wire:model="staffRole"
                :options="[
                    ['id' => 'operator', 'name' => 'Operator — Can execute commands & approve requests'],
                    ['id' => 'maintenance', 'name' => 'Maintenance — Can register & manage nodes'],
                    ['id' => 'viewer', 'name' => 'Viewer — Read-only access, can submit requests'],
                ]" required />
            <x-slot name="actions">
                <x-button label="Cancel" @click="$wire.showCreate = false" />
                <x-button label="Add Staff" class="btn-primary" type="submit" spinner="createStaff" icon="o-check" />
            </x-slot>
        </x-form>
    </x-modal>
</div>
