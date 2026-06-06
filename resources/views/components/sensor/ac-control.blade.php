@props(['id', 'label' => 'AC Control', 'status' => 'Active', 'target' => 24])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 rounded-xl p-6 flex flex-col justify-between shadow-sm dark:shadow-none']) }}>
    <div class="flex justify-between items-start mb-4">
        <div class="flex items-center gap-2 text-slate-500 dark:text-on-surface-variant font-label-md text-label-md">
            <span class="material-symbols-outlined {{ $status == 'Active' ? 'text-cyan-600 dark:text-cyan-400' : 'text-slate-400 dark:text-slate-500' }}" style="font-variation-settings: 'FILL' 1;">mode_fan</span>
            {{ $label }}
        </div>
        <div class="flex items-center gap-1">
            <span class="{{ $status == 'Active' ? 'bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400' : 'bg-slate-100 dark:bg-slate-500/10 text-slate-600 dark:text-slate-500' }} px-2 py-0.5 rounded text-[11px] font-bold border {{ $status == 'Active' ? 'border-blue-200 dark:border-blue-500/20' : 'border-slate-200 dark:border-slate-500/20' }} mr-2">{{ $status }}</span>
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full cursor-pointer text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-[20px]">more_vert</span>
                </label>
                <ul tabindex="0" class="dropdown-content z-[20] menu p-2 shadow-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg w-36">
                    <li><a wire:click="$dispatch('edit-sensor', { id: {{ $id }} })" class="py-2 text-xs flex items-center gap-2 text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded"><span class="material-symbols-outlined text-sm">edit</span> Edit Sensor</a></li>
                    <li><a wire:click="deleteSensor({{ $id }})" wire:confirm="Are you sure you want to delete this sensor?" class="py-2 text-xs text-error-600 dark:text-error flex items-center gap-2 hover:bg-error-50 dark:hover:bg-error/10 rounded"><span class="material-symbols-outlined text-sm">delete</span> Delete Sensor</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="flex items-baseline gap-2 mb-4">
        <span class="font-display text-4xl font-bold text-slate-800 dark:text-on-surface">{{ $target }}</span>
        <span class="font-h3 text-xl text-slate-500 dark:text-on-surface-variant">°C</span>
        <span class="ml-2 text-xs text-slate-500 dark:text-on-surface-variant">Target</span>
    </div>
    <button class="w-full mt-auto py-2 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-on-surface rounded hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-sm font-medium flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-sm">settings_remote</span>
        Control Settings
    </button>
</div>
