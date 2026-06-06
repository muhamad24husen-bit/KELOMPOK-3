@props(['id', 'label', 'value', 'unit', 'max' => 100, 'color' => '#34d399'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 rounded-xl p-6 relative group overflow-hidden shadow-sm dark:shadow-none']) }}>
    <div class="flex justify-between items-start mb-4 relative z-10">
        <div class="flex items-center gap-2 text-slate-500 dark:text-on-surface-variant font-label-md text-label-md">
            <span class="material-symbols-outlined" style="color: {{ $color }}">speed</span>
            {{ $label }}
        </div>
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
    
    <div class="flex flex-col items-center justify-center relative">
        <div class="relative w-32 h-32 flex items-center justify-center">
            {{-- Simple SVG Gauge --}}
            <svg class="w-full h-full transform -rotate-90">
                <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8" fill="transparent" class="text-slate-200 dark:text-slate-800" />
                <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8" fill="transparent" 
                        stroke-dasharray="364.4" 
                        stroke-dashoffset="{{ 364.4 - (364.4 * min($value, $max) / $max) }}" 
                        style="color: {{ $color }}; transition: stroke-dashoffset 0.5s ease;" />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="font-display text-2xl font-bold text-slate-800 dark:text-on-surface">{{ $value }}</span>
                <span class="text-[10px] text-slate-500 dark:text-on-surface-variant uppercase tracking-widest">{{ $unit }}</span>
            </div>
        </div>
    </div>
</div>
