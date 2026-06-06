<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\User;
use App\Imports\UserRoleImport;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new class extends Component {
    use WithFileUploads, WithPagination, Toast;

    public string $search = '';
    public string $roleFilter = '';
    public $importFile;
    public bool $showImportModal = false;
    public array $importResult = [];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedRoleFilter()
    {
        $this->resetPage();
    }

    #[On('refreshUserIndex')]
    public function render()
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        if ($this->roleFilter) {
            $query->role($this->roleFilter); // Spatie scope
        }

        $users = $query->latest()->paginate(10);
        $availableRoles = Role::pluck('name')->toArray();

        // Dynamic stats using Spatie roles
        $totalUsers = User::count();
        $thisMonthUsers = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();
        $lastMonthUsers = User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)->count();
        $userGrowth = $lastMonthUsers > 0
            ? round((($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100, 1)
            : ($thisMonthUsers > 0 ? 100 : 0);

        $adminCount = User::role('super_admin')->count();
        $clientCount = User::role('client')->count();
        $staffCount = User::role(['operator', 'maintenance'])->count();

        return $this->view([
            'users' => $users,
            'availableRoles' => $availableRoles,
            'totalUsers' => $totalUsers,
            'userGrowth' => $userGrowth,
            'adminCount' => $adminCount,
            'clientCount' => $clientCount,
            'staffCount' => $staffCount,
        ]);
    }

    public function deleteUser($userId)
    {
        $user = User::find($userId);
        if ($user && $user->id !== auth()->id()) {
            $user->delete();
            $this->success('User berhasil dihapus.');
        } else {
            $this->error('Tidak dapat menghapus user ini.');
        }
    }

    public function openImportModal()
    {
        $this->reset(['importFile', 'importResult']);
        $this->showImportModal = true;
    }

    public function importRoles()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            $import = new UserRoleImport();
            Excel::import($import, $this->importFile->getRealPath());

            $this->importResult = [
                'success' => true,
                'created' => $import->created,
                'updated' => $import->updated,
                'skipped' => $import->skipped,
                'errors'  => $import->errors,
            ];

            if ($import->created > 0) {
                $this->success("{$import->created} user baru berhasil ditambahkan!");
            }

            if ($import->updated > 0) {
                $this->success("{$import->updated} user role berhasil diperbarui!");
            }

            if ($import->skipped > 0) {
                $this->warning("{$import->skipped} baris dilewati karena error.");
            }

        } catch (\Exception $e) {
            $this->importResult = [
                'success' => false,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors'  => [$e->getMessage()],
            ];
            $this->error('Import gagal: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = ['name', 'email', 'role'];
        $availableRoles = Role::pluck('name')->toArray();

        // Build CSV content with example rows
        $csvContent = implode(',', $headers) . "\n";
        $csvContent .= "John Doe,john@example.com," . ($availableRoles[0] ?? 'operator') . "\n";
        if (count($availableRoles) > 1) {
            $csvContent .= "Jane Smith,jane@example.com," . $availableRoles[1] . "\n";
        }

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, 'template_import_user.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportPdf()
    {
        $users = User::all();

        return response()->streamDownload(function () use ($users) {
            try {
                echo Pdf::view('pdf.users', ['users' => $users])
                    ->format('a4')
                    ->withBrowsershot(function ($browsershot) {
                        $browsershot->noSandbox()->waitUntilNetworkIdle();
                    })
                    ->getBrowsershot()
                    ->pdf();
            } catch (\Throwable $e) {
                echo \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.users', ['users' => $users])
                    ->setPaper('a4')
                    ->output();
            }
        }, 'users-export.pdf');

    }
};
?>

<div class="p-margin-page max-w-container-max mx-auto space-y-gutter">
    <!-- Header Section -->
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <nav aria-label="Breadcrumb" class="flex text-sm text-outline mb-2 font-label-sm">
                <ol class="inline-flex items-center space-x-2">
                </ol>
            </nav>
            <h1 class="font-h1 text-h1 text-slate-800 dark:text-on-surface">User Access Management</h1>
        </div>
        <div class="flex items-center gap-3">


            <button @click="$dispatch('openUserCreateDrawer')"
                class="flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all active:scale-[0.98] shadow-lg shadow-blue-900/20">
                <span class="material-symbols-outlined text-[20px]">person_add</span>
                <span>Add New User</span>
            </button>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-gutter">
        <div
            class="bg-white dark:bg-surface-container rounded-xl p-unit-lg border border-slate-200 dark:border-outline-variant/30 shadow-sm dark:shadow-none flex flex-col justify-between transition-colors duration-300">
            <div class="flex justify-between items-start mb-4">
                <span class="font-label-md text-slate-500 dark:text-outline">Total Users</span>
                <span class="material-symbols-outlined text-slate-400 dark:text-outline">group</span>
            </div>
            <div>
                <div class="font-display text-display text-slate-800 dark:text-on-surface">{{ $totalUsers }}</div>
                <div class="font-body-sm text-body-sm {{ $userGrowth >= 0 ? 'text-blue-600 dark:text-primary' : 'text-error-600 dark:text-error' }} mt-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">{{ $userGrowth >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    {{ $userGrowth >= 0 ? '+' : '' }}{{ $userGrowth }}% from last month
                </div>
            </div>
        </div>
        <div
            class="bg-white dark:bg-surface-container rounded-xl p-unit-lg border border-slate-200 dark:border-outline-variant/30 shadow-sm dark:shadow-none flex flex-col justify-between transition-colors duration-300">
            <div class="flex justify-between items-start mb-4">
                <span class="font-label-md text-slate-500 dark:text-outline">Administrators</span>
                <span class="material-symbols-outlined text-slate-400 dark:text-outline">admin_panel_settings</span>
            </div>
            <div>
                <div class="font-display text-display text-slate-800 dark:text-on-surface">
                    {{ $adminCount }}
                </div>
                <div class="font-body-sm text-body-sm text-slate-500 dark:text-outline mt-1 flex items-center gap-1">
                    Stable
                </div>
            </div>
        </div>
        <div
            class="bg-white dark:bg-surface-container rounded-xl p-unit-lg border border-slate-200 dark:border-outline-variant/30 shadow-sm dark:shadow-none flex flex-col justify-between transition-colors duration-300">
            <div class="flex justify-between items-start mb-4">
                <span class="font-label-md text-slate-500 dark:text-outline">Active Clients</span>
                <span class="material-symbols-outlined text-slate-400 dark:text-outline">corporate_fare</span>
            </div>
            <div>
                <div class="font-display text-display text-slate-800 dark:text-on-surface">
                    {{ $clientCount }}
                </div>
                <div class="font-body-sm text-body-sm text-slate-500 dark:text-outline mt-1 flex items-center gap-1">
                    Via Spatie Role
                </div>
            </div>
        </div>
        <div
            class="bg-white dark:bg-surface-container rounded-xl p-unit-lg border border-slate-200 dark:border-outline-variant/30 shadow-sm dark:shadow-none flex flex-col justify-between transition-colors duration-300">
            <div class="flex justify-between items-start mb-4">
                <span class="font-label-md text-slate-500 dark:text-outline">Staff / Operators</span>
                <span class="material-symbols-outlined text-slate-400 dark:text-outline">engineering</span>
            </div>
            <div>
                <div class="font-display text-display text-slate-800 dark:text-on-surface">
                    {{ $staffCount }}
                </div>
                <div class="font-body-sm text-body-sm text-slate-500 dark:text-outline mt-1 flex items-center gap-1">
                    Operator & Maintenance
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Section -->
    <div
        class="bg-white dark:bg-surface-container rounded-xl border border-slate-200 dark:border-outline-variant/30 overflow-hidden shadow-sm dark:shadow-none flex flex-col transition-colors duration-300">
        <!-- Table Toolbar -->
        <div
            class="p-unit-lg border-b border-slate-200 dark:border-outline-variant/30 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white dark:bg-surface-container-high/50">
            <div class="relative w-full sm:w-96">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-outline text-lg">search</span>
                <input wire:model.live.debounce.300ms="search"
                    class="w-full bg-slate-50 dark:bg-surface border border-slate-200 dark:border-outline-variant text-slate-800 dark:text-on-surface text-sm rounded-lg pl-10 pr-4 py-2 focus:outline-none focus:border-blue-500 dark:focus:border-primary focus:ring-1 focus:ring-blue-500 dark:focus:ring-primary transition-colors placeholder-slate-400 dark:placeholder-outline"
                    placeholder="Search by name or email..." type="text" />
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-48">

                    <select wire:model.live="roleFilter"
                        class="w-full appearance-none bg-slate-50 dark:bg-surface border border-slate-200 dark:border-outline-variant text-slate-800 dark:text-on-surface text-sm rounded-lg pl-4 pr-10 py-2 focus:outline-none focus:border-blue-500 dark:focus:border-primary focus:ring-1 focus:ring-blue-500 dark:focus:ring-primary transition-colors cursor-pointer font-body-sm">
                        <option value="">All Roles</option>
                        @foreach($availableRoles as $r)
                            <option value="{{ $r }}">{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                        @endforeach
                    </select>
                    <span
                        class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-outline text-lg pointer-events-none">expand_more</span>

                </div>
                <button wire:click="openImportModal"
                    class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-all active:scale-[0.98] shadow-lg shadow-emerald-900/20 border border-emerald-500/50">
                    <span class="material-symbols-outlined text-[20px]">upload_file</span>
                    <span>Import Roles</span>
                </button>
                <x-export-button />


            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-slate-50 dark:bg-surface-container-low border-b border-slate-200 dark:border-outline-variant/30 font-label-sm text-label-sm text-slate-600 dark:text-outline uppercase tracking-wider">
                        <th class="px-6 py-4 font-semibold">User</th>
                        <th class="px-6 py-4 font-semibold">Role</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold">Joined Date</th>
                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="font-body-sm text-body-sm">
                    @forelse($users as $user)
                        <tr
                            class="border-b border-slate-200 dark:border-outline-variant/10 hover:bg-slate-50 dark:hover:bg-surface-container-high/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img alt="{{ $user->name }}"
                                        class="w-8 h-8 rounded-full border border-slate-200 dark:border-outline-variant/50 object-cover"
                                        src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0D8ABC&color=fff" />
                                    <div>
                                        <div class="font-medium text-slate-800 dark:text-on-surface">{{ $user->name }}</div>
                                        <div class="text-slate-500 dark:text-outline text-xs">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-blue-100 dark:bg-primary-container/20 text-blue-700 dark:text-primary border border-blue-200 dark:border-primary-container/30 uppercase">
                                    {{ $user->role ?? 'User' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php $lastActive = $user->updated_at ?? $user->created_at; @endphp
                                @if($lastActive->diffInDays(now()) < 30)
                                    <span class="flex items-center gap-1.5 text-emerald-600 dark:text-secondary">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-secondary shadow-[0_0_4px_rgba(183,200,225,0.6)]"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="flex items-center gap-1.5 text-slate-500 dark:text-outline">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-outline"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-outline">{{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="$dispatch('openUserEditDrawer', { userId: {{ $user->id }} })" aria-label="Edit"
                                        class="p-1.5 rounded-md text-slate-400 dark:text-outline hover:text-blue-600 dark:hover:text-primary hover:bg-slate-100 dark:hover:bg-surface-bright transition-colors tooltip"
                                        data-tip="Edit">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button wire:click="deleteUser({{ $user->id }})" wire:confirm="Yakin ingin menghapus user {{ $user->name }}?" aria-label="Delete"
                                        class="p-1.5 rounded-md text-slate-400 dark:text-outline hover:text-error-600 dark:hover:text-error hover:bg-red-50 dark:hover:bg-surface-bright transition-colors tooltip"
                                        data-tip="Delete">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-outline">No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            class="p-4 border-t border-slate-200 dark:border-outline-variant/30 flex items-center justify-between bg-white dark:bg-surface-container-low font-body-sm text-slate-600 dark:text-outline">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Import Role Modal -->
    <x-modal wire:model="showImportModal" title="Import User"
        subtitle="Upload file Excel untuk menambahkan user baru secara massal" separator>
        <div class="space-y-6">

            {{-- Info Box --}}
            <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/50 rounded-xl p-4">
                <div class="flex gap-3">
                    <span
                        class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-[22px] mt-0.5">info</span>
                    <div class="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                        <p class="font-semibold">Format file yang dibutuhkan:</p>
                        <ul class="list-disc list-inside space-y-0.5 text-blue-700 dark:text-blue-400">
                            <li>Kolom A: <strong>name</strong> — nama lengkap user</li>
                            <li>Kolom B: <strong>email</strong> — email user (harus unik)</li>
                            <li>Kolom C: <strong>role</strong> — role yang akan di-assign</li>
                        </ul>
                        <p class="mt-2">Role yang tersedia:
                            @foreach($availableRoles as $r)
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-semibold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50 uppercase">{{ $r }}</span>
                            @endforeach
                        </p>
                        <div class="mt-2 flex items-center gap-1.5 text-amber-700 dark:text-amber-400">
                            <span class="material-symbols-outlined text-[16px]">key</span>
                            <span>Password default: <strong>password123</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Download Template --}}
            <div
                class="flex items-center justify-between bg-slate-50 dark:bg-surface-container-low rounded-xl p-4 border border-slate-200 dark:border-outline-variant/30">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <span
                            class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-[20px]">description</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-on-surface">Template Excel</p>
                        <p class="text-xs text-slate-500 dark:text-outline">Download contoh format file</p>
                    </div>
                </div>
                <button wire:click="downloadTemplate"
                    class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700/50 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors">
                    <span class="material-symbols-outlined text-[16px]">download</span>
                    Download
                </button>
            </div>

            {{-- File Upload --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-on-surface-variant mb-2">Upload
                    File</label>
                <div x-data="{ isDragging: false }" x-on:dragover.prevent="isDragging = true"
                    x-on:dragleave.prevent="isDragging = false" x-on:drop.prevent="isDragging = false"
                    :class="isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/20' : 'border-slate-300 dark:border-outline-variant bg-slate-50 dark:bg-surface'"
                    class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer hover:border-blue-400 dark:hover:border-blue-500">
                    <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                    <div class="space-y-3">
                        <div
                            class="w-14 h-14 mx-auto rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <span
                                class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-[28px]">cloud_upload</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-700 dark:text-on-surface">
                                Drag & drop file atau <span
                                    class="text-blue-600 dark:text-blue-400 underline">browse</span>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-outline mt-1">Format: .xlsx, .xls, .csv (maks
                                2MB)</p>
                        </div>
                    </div>
                </div>

                {{-- File preview --}}
                @if($importFile)
                    <div
                        class="mt-3 flex items-center gap-3 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/50 rounded-lg p-3">
                        <span
                            class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-[20px]">check_circle</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300 truncate">
                                {{ $importFile->getClientOriginalName() }}
                            </p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                {{ number_format($importFile->getSize() / 1024, 1) }} KB
                            </p>
                        </div>
                        <button wire:click="$set('importFile', null)"
                            class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>
                @endif

                @error('importFile')
                    <p class="mt-2 text-sm text-red-600 dark:text-error flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">error</span>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Import Result --}}
            @if(!empty($importResult))
                <div
                    class="rounded-xl border {{ $importResult['success'] ? 'border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-950/20' : 'border-red-200 dark:border-red-800/50 bg-red-50 dark:bg-red-950/20' }} p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <span
                            class="material-symbols-outlined text-[20px] {{ $importResult['success'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $importResult['success'] ? 'task_alt' : 'error' }}
                        </span>
                        <span
                            class="text-sm font-semibold {{ $importResult['success'] ? 'text-emerald-800 dark:text-emerald-300' : 'text-red-800 dark:text-red-300' }}">
                            Hasil Import
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-white dark:bg-surface-container rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $importResult['created'] ?? 0 }}
                            </div>
                            <div class="text-xs text-slate-500 dark:text-outline">User baru</div>
                        </div>
                        <div class="bg-white dark:bg-surface-container rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $importResult['updated'] ?? 0 }}
                            </div>
                            <div class="text-xs text-slate-500 dark:text-outline">Diperbarui</div>
                        </div>
                        <div class="bg-white dark:bg-surface-container rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                {{ $importResult['skipped'] ?? 0 }}
                            </div>
                            <div class="text-xs text-slate-500 dark:text-outline">Dilewati</div>
                        </div>
                    </div>

                    @if(!empty($importResult['errors']))
                        <details class="group">
                            <summary
                                class="text-sm font-medium text-red-700 dark:text-red-400 cursor-pointer flex items-center gap-1 hover:underline">
                                <span
                                    class="material-symbols-outlined text-[16px] group-open:rotate-90 transition-transform">chevron_right</span>
                                Lihat detail error ({{ count($importResult['errors']) }})
                            </summary>
                            <ul
                                class="mt-2 space-y-1 pl-5 text-xs text-red-600 dark:text-red-400 list-disc max-h-32 overflow-y-auto">
                                @foreach($importResult['errors'] as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @endif
        </div>

        <x-slot name="actions">
            <x-button label="Tutup" @click="$wire.showImportModal = false" />
            <x-button label="Import Users" class="btn-primary" wire:click="importRoles" spinner="importRoles"
                icon="o-arrow-up-tray" :disabled="!$importFile" />
        </x-slot>
    </x-modal>

    <livewire:pages::admin.user.create />
</div>