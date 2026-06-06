@props(['id', 'label', 'value', 'unit', 'color' => '#b4c5ff', 'data' => []])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 rounded-xl p-6 relative group overflow-hidden shadow-sm dark:shadow-none']) }}>
    <div class="absolute top-0 right-0 w-32 h-32 bg-{{ str_replace('#', '', $color) }}/5 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
    <div class="flex justify-between items-start mb-4 relative z-10">
        <div class="flex items-center gap-2 text-slate-500 dark:text-on-surface-variant font-label-md text-label-md">
            <span class="material-symbols-outlined" style="color: {{ $color }}">show_chart</span>
            {{ $label }}
        </div>
        <div class="flex items-center gap-1">
            <span class="bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded text-[11px] font-bold border border-emerald-200 dark:border-emerald-500/20 mr-2">Active</span>
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full cursor-pointer text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors relative z-20">
                    <span class="material-symbols-outlined text-[20px]">more_vert</span>
                </label>
                <ul tabindex="0" class="dropdown-content z-[30] menu p-2 shadow-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg w-36">
                    <li><a wire:click="$dispatch('edit-sensor', { id: {{ $id }} })" class="py-2 text-xs flex items-center gap-2 text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded"><span class="material-symbols-outlined text-sm">edit</span> Edit Sensor</a></li>
                    <li><a wire:click="deleteSensor({{ $id }})" wire:confirm="Are you sure you want to delete this sensor?" class="py-2 text-xs text-error-600 dark:text-error flex items-center gap-2 hover:bg-error-50 dark:hover:bg-error/10 rounded"><span class="material-symbols-outlined text-sm">delete</span> Delete Sensor</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="flex items-baseline gap-2 relative z-10 mb-4">
        <span class="font-display text-4xl font-bold text-slate-800 dark:text-on-surface">{{ $value }}</span>
        <span class="font-h3 text-xl text-slate-500 dark:text-on-surface-variant">{{ $unit }}</span>
    </div>
    <div class="h-24 w-full" wire:ignore>
        <canvas id="chart-{{ $id }}"></canvas>
    </div>

    <script>
        document.addEventListener('livewire:navigated', () => {
            const ctx = document.getElementById('chart-{{ $id }}')?.getContext('2d');
            if (!ctx) return;

            const gradient = ctx.createLinearGradient(0, 0, 0, 100);
            gradient.addColorStop(0, '{{ $color }}44');
            gradient.addColorStop(1, '{{ $color }}00');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json(array_keys($data)),
                    datasets: [{
                        data: @json(array_values($data)),
                        borderColor: '{{ $color }}',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    scales: { x: { display: false }, y: { display: false } }
                }
            });
        });
    </script>
</div>
