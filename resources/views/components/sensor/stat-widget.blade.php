@props(['id', 'label', 'value', 'unit', 'icon' => 'sensors', 'color' => '#8d90a0'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 rounded-xl p-6 relative group overflow-hidden shadow-sm dark:shadow-none']) }}>
    <div class="flex justify-between items-start mb-4">
        <div class="flex items-center gap-2 text-slate-500 dark:text-on-surface-variant font-label-md text-label-md">
            <span class="material-symbols-outlined" style="color: {{ $color }}">{{ $icon }}</span>
            {{ $label }}
        </div>
        <div class="flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse mr-2"></span>
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
    <div class="flex items-baseline gap-2">
        <span class="font-display text-4xl font-bold text-slate-800 dark:text-on-surface">{{ $value }}</span>
        <span class="font-h3 text-xl text-slate-500 dark:text-on-surface-variant">{{ $unit }}</span>
    </div>
    <div class="mt-4 flex items-center gap-2 text-slate-500 dark:text-on-surface-variant font-label-sm text-label-sm">
        <span class="material-symbols-outlined text-sm">update</span>
        <span>Updated just now</span>
    </div>
</div>
