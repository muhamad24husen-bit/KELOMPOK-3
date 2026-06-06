<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Role;
use App\Mail\WelcomeUserMail;

new class extends Component {
    use Toast;

    public bool $userCreateDrawer = false;

    public string $name = '';
    public string $email = '';
    public string $role = '';
    public string $password = '';
    public bool $sendEmail = true;
    public bool $showPassword = false;
    public ?int $editingUserId = null; // null = mode create, ada ID = mode edit

    protected $listeners = [
        'openUserCreateDrawer' => 'openDrawer',
        'openUserEditDrawer'   => 'openEditDrawer',
    ];

    public function openDrawer()
    {
        $this->reset(['name', 'email', 'role', 'password', 'sendEmail', 'showPassword', 'editingUserId']);
        $this->sendEmail = true;
        $this->userCreateDrawer = true;
    }

    public function openEditDrawer($userId)
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $userId;
        $this->name          = $user->name;
        $this->email         = $user->email;
        $this->role          = $user->getRoleNames()->first() ?? '';
        $this->password      = ''; // kosong — tidak tampilkan password lama
        $this->sendEmail     = false;
        $this->showPassword  = false;
        $this->userCreateDrawer = true;
    }

    public function generatePassword()
    {
        $this->password = Str::random(12);
    }

    public function togglePasswordVisibility()
    {
        $this->showPassword = !$this->showPassword;
    }

    public function createUser()
    {
        if ($this->editingUserId) {
            // ── MODE EDIT ────────────────────────────────────────────
            $this->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $this->editingUserId,
                'role'  => 'required|string',
                'password' => 'nullable|min:8',
            ]);

            $user = User::findOrFail($this->editingUserId);
            $updateData = [
                'name'  => $this->name,
                'email' => $this->email,
                'role'  => $this->role,
            ];

            // Hanya update password jika diisi
            if (!empty($this->password)) {
                $updateData['password'] = Hash::make($this->password);
            }

            $user->update($updateData);

            // Sync role Spatie
            if (Role::where('name', $this->role)->exists()) {
                $user->syncRoles([$this->role]);
            }

            $this->success('User berhasil diperbarui!');
        } else {
            // ── MODE CREATE ──────────────────────────────────────────
            $this->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'role'     => 'required|string',
                'password' => 'required|min:8',
            ]);

            $plainPassword = $this->password;

            $user = User::create([
                'name'     => $this->name,
                'email'    => $this->email,
                'password' => Hash::make($plainPassword),
                'role'     => $this->role,
            ]);

            // Assign role Spatie
            if (Role::where('name', $this->role)->exists()) {
                $user->assignRole($this->role);
            }

            // Kirim welcome email jika diminta
            if ($this->sendEmail) {
                try {
                    Mail::to($user->email)->queue(new WelcomeUserMail($user, $plainPassword));
                } catch (\Throwable $e) {
                    $this->warning('User dibuat, tapi email gagal dikirim: ' . $e->getMessage());
                }
            }

            $this->success('User created successfully!');
        }

        $this->editingUserId    = null;
        $this->userCreateDrawer = false;
        $this->dispatch('refreshUserIndex');
    }
}; ?>

<div>
    <x-drawer wire:model="userCreateDrawer" title="Add New User" right class="w-full sm:w-[440px] bg-slate-900" separator>
        
        <x-form wire:submit="createUser" class="space-y-6">
            
            {{-- Full Name --}}
            <x-input label="Full Name" wire:model="name" placeholder="e.g. Jane Doe" required icon="o-user" />

            {{-- Email Address --}}
            <x-input label="Email Address" wire:model="email" type="email" placeholder="jane.doe@example.com" required icon="o-envelope" />

            {{-- System Role --}}
            <x-select 
                label="System Role" 
                wire:model="role" 
                placeholder="Select an access level" 
                :options="[
                    ['id' => 'super_admin', 'name' => 'Super Admin — Full System Access'],
                    ['id' => 'client',      'name' => 'Client — Building Owner Access'],
                    ['id' => 'operator',    'name' => 'Operator — Standard Operations'],
                    ['id' => 'maintenance', 'name' => 'Maintenance — Hardware Management'],
                    ['id' => 'viewer',      'name' => 'Viewer — Read-only Access'],
                ]" 
                icon="o-briefcase"
                required
            />

            {{-- Initial Password --}}
            <div class="pt-4 border-t border-slate-800 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-400">Password Settings</span>
                    <button wire:click="generatePassword" type="button" class="text-blue-500 text-xs hover:underline">Generate Random</button>
                </div>
                
                <x-input 
                    label="Initial Password"
                    wire:model="password" 
                    :type="$showPassword ? 'text' : 'password'" 
                    placeholder="••••••••" 
                    icon="o-key"
                    required
                >
                    <x-slot:append>
                        <x-button 
                            :icon="$showPassword ? 'o-eye-slash' : 'o-eye'" 
                            class="btn-ghost btn-sm" 
                            wire:click="togglePasswordVisibility" 
                        />
                    </x-slot:append>
                </x-input>
            </div>

            {{-- Options --}}
            <div class="pt-4 border-t border-slate-800">
                <x-checkbox wire:model="sendEmail" label="Send welcome email with credentials" />
            </div>

            <x-slot name="actions">
                <x-button label="Cancel" @click="$wire.userCreateDrawer = false" />
                <x-button
                    :label="$editingUserId ? 'Update User' : 'Create User'"
                    class="btn-primary"
                    type="submit"
                    spinner="createUser"
                />
            </x-slot>

        </x-form>

    </x-drawer>
</div>
