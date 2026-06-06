@props(['id', 'title' => 'Live Trends', 'datasets' => [], 'labels' => []])

<div {{ $attributes->merge(['class' => 'mt-8']) }}>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-slate-800 dark:text-on-surface flex items-center gap-2">
            <i class="ph ph-trend-up text-primary"></i>
            {{ $title }}
        </h3>
        <div class="flex items-center gap-4 text-slate-500 dark:text-on-surface-variant text-xs font-semibold">
            @foreach($datasets as $dataset)
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $dataset['color'] }}"></span>
                    {{ $dataset['label'] }}
                </div>
            @endforeach
            <div class="dropdown dropdown-end ml-2">
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
    <div class="bg-white dark:bg-surface-container border border-slate-200 dark:border-slate-800 rounded-xl p-6 h-[400px] shadow-sm dark:shadow-none">
        <canvas id="chart-{{ $id }}" wire:ignore></canvas>
    </div>

    <script>
        document.addEventListener('livewire:navigated', () => {
            const canvas = document.getElementById('chart-{{ $id }}');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            
            const chartDatasets = @json($datasets).map(ds => {
                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, ds.color + '66');
                gradient.addColorStop(1, ds.color + '00');
                
                return {
                    label: ds.label,
                    data: ds.data,
                    borderColor: ds.color,
                    backgroundColor: gradient,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: ds.color,
                    pointBorderColor: '#11131b',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                };
            });

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($labels),
                    datasets: chartDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#282a32',
                            titleColor: '#e1e2ed',
                            bodyColor: '#c3c6d7',
                            borderColor: '#434655',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true,
                            boxPadding: 4
                        }
                    },
                    interaction: { mode: 'nearest', axis: 'x', intersect: false },
                    scales: {
                        x: {
                            grid: { color: '#32343d', drawBorder: false },
                            ticks: { color: '#8d90a0', font: { family: 'Inter', size: 11 } }
                        },
                        y: {
                            grid: { color: '#32343d', drawBorder: false },
                            ticks: { color: '#8d90a0', font: { family: 'Inter', size: 11 } }
                        }
                    }
                }
            });
        });
    </script>
</div>
