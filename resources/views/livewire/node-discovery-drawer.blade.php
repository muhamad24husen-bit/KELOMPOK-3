<div>
    {{-- ═══════════════ TRIGGER BUTTON (for Navbar) ═══════════════ --}}
    <button wire:click="$set('drawerOpen', true)"
        class="p-2 text-slate-400 hover:bg-slate-900/50 hover:text-blue-400 transition-colors cursor-pointer active:scale-95 duration-150 rounded-full relative"
        title="Node Discovery Queue" id="node-discovery-trigger">
        <span class="material-symbols-outlined">cpu</span>
        @if($pendingCount > 0)
            <span
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center bg-rose-500 text-white text-[10px] font-bold rounded-full animate-pulse shadow-lg shadow-rose-500/40">
                {{ $pendingCount }}
            </span>
        @endif
    </button>

    {{-- ═══════════════ DRAWER ═══════════════ --}}
    <template x-teleport="body">
        <div x-data="{ open: @entangle('drawerOpen') }" x-show="open" x-cloak class="fixed inset-0" style="z-index: 60;"
            @keydown.escape.window="open = false">
            {{-- Backdrop --}}
            <div x-show="open" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                @click="open = false"></div>

            {{-- Panel --}}
            <div x-show="open" x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute right-0 top-0 h-full w-full max-w-[600px] bg-white dark:bg-[#0f111a] border-l border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col transition-colors duration-300">
                {{-- Header --}}
                <header
                    class="flex-none px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#1a1c26] flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($configMode)
                            <button wire:click="backToList"
                                class="text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 transition-colors p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-800">
                                <span class="material-symbols-outlined">arrow_back</span>
                            </button>
                            <h2 class="text-[20px] font-semibold text-slate-800 dark:text-slate-100">Konfigurasi Node</h2>
                        @else
                            <div class="relative flex h-3 w-3">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-500 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                            </div>
                            <h2 class="text-[20px] font-semibold text-slate-800 dark:text-slate-100">Daftar Perangkat Baru</h2>
                        @endif
                    </div>
                    <button @click="open = false"
                        class="text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 transition-colors p-1 rounded hover:bg-slate-200 dark:hover:bg-slate-800">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </header>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;"
                    wire:poll.5s>
                    @if($configMode)
                        {{-- ─── CONFIGURATION FORM ─── --}}
                        <div class="space-y-5 px-6 py-4">
                            {{-- Device Info (Read-Only) --}}
                            <div class="bg-[#161922] rounded-xl p-4 border border-slate-800">
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest mb-3 font-bold">Identitas
                                    Perangkat</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <span class="text-[10px] text-slate-500 uppercase tracking-wider">MAC Address</span>
                                        <p class="text-slate-200 font-mono text-sm mt-0.5">
                                            {{ $configuringNode['mac'] ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-500 uppercase tracking-wider">Chip</span>
                                        <p class="text-slate-200 text-sm mt-0.5">{{ $configuringNode['chip'] ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-500 uppercase tracking-wider">Firmware</span>
                                        <p class="text-slate-200 text-sm mt-0.5">{{ $configuringNode['firmware'] ?? '—' }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-500 uppercase tracking-wider">Ditemukan</span>
                                        <p class="text-slate-200 text-sm mt-0.5">{{ $configuringNode['time'] ?? '—' }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Node Name --}}
                            <div>
                                <label class="text-xs text-slate-400 uppercase tracking-wider font-medium block mb-1.5">Nama
                                    Node (Opsional)</label>
                                <input wire:model="nodeName" type="text" placeholder="Contoh: Node Server Utama"
                                    class="w-full bg-[#1a1c26] border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-blue-500 transition-colors" />
                            </div>

                            {{-- Location Selection --}}
                            <div class="space-y-3">
                                <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Penempatan Lokasi
                                </p>

                                <div>
                                    <label
                                        class="text-xs text-slate-400 uppercase tracking-wider font-medium block mb-1.5">Pilih
                                        Gedung / Klien</label>
                                    <select wire:model.live="selectedClient"
                                        class="w-full bg-[#1a1c26] border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-blue-500 transition-colors">
                                        <option value="">— Pilih Gedung —</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client['id'] }}">{{ $client['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="text-xs text-slate-400 uppercase tracking-wider font-medium block mb-1.5">Pilih
                                        Ruangan</label>
                                    <select wire:model.live="selectedRoom"
                                        class="w-full bg-[#1a1c26] border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-blue-500 transition-colors disabled:opacity-40"
                                        @disabled(!$selectedClient)>
                                        <option value="">— Pilih Ruangan —</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room['id'] }}">{{ $room['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- ═══ SENSOR CONFIGURATION (Manual, one-by-one) ═══ --}}
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-[10px] text-blue-400 uppercase tracking-widest font-bold">Konfigurasi Sensor</p>
                                    @if(count($configuredSensors) > 0)
                                        <span class="text-[10px] text-slate-500 font-medium">{{ count($configuredSensors) }} sensor ditambahkan</span>
                                    @endif
                                </div>

                                {{-- Sensor Type --}}
                                <div>
                                    <label class="text-xs text-slate-400 uppercase tracking-wider font-medium block mb-1.5">Tipe Sensor</label>
                                    <select wire:model="sensorType"
                                        class="w-full bg-[#1a1c26] border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-blue-500 transition-colors">
                                        <option value="">— Pilih Tipe —</option>
                                        <option value="Environmental">Environmental (Temp/Humidity)</option>
                                        <option value="Power">Power Consumption Monitor</option>
                                        <option value="Motion">PIR Motion Detector</option>
                                        <option value="Air Quality">Air Quality (VOC/CO2)</option>
                                        <option value="Door">Contact Sensor</option>
                                    </select>
                                </div>

                                {{-- Measurement & Unit --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-slate-400 uppercase tracking-wider font-medium block mb-1.5">Pengukuran</label>
                                        <select wire:model.live="measurementType"
                                            class="w-full bg-[#1a1c26] border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-blue-500 transition-colors">
                                            <option value="temperature">Temperature</option>
                                            <option value="humidity">Humidity</option>
                                            <option value="power">Power / Energy</option>
                                            <option value="voltage">Voltage</option>
                                            <option value="percentage">Percentage</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 uppercase tracking-wider font-medium block mb-1.5">Satuan</label>
                                        <select wire:model="unit"
                                            class="w-full bg-[#1a1c26] border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-blue-500 transition-colors">
                                            @switch($measurementType)
                                                @case('temperature')
                                                    <option value="celsius">Celsius (°C)</option>
                                                    <option value="fahrenheit">Fahrenheit (°F)</option>
                                                    @break
                                                @case('humidity')
                                                @case('percentage')
                                                    <option value="percent">Percent (%)</option>
                                                    @break
                                                @case('power')
                                                    <option value="watt">Watt (W)</option>
                                                    @break
                                                @case('voltage')
                                                    <option value="volt">Volt (V)</option>
                                                    @break
                                            @endswitch
                                        </select>
                                    </div>
                                </div>

                                {{-- Default Visualization Picker --}}
                                <div class="space-y-2">
                                    <label class="text-xs text-slate-400 uppercase tracking-wider font-medium block">Visualisasi</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        @php
                                            $vizOptions = [
                                                ['id' => 'line',   'icon' => 'show_chart', 'label' => 'Line Chart'],
                                                ['id' => 'gauge',  'icon' => 'speed',      'label' => 'Gauge'],
                                                ['id' => 'widget', 'icon' => 'dashboard',  'label' => 'Stat Widget'],
                                                ['id' => 'light',  'icon' => 'lightbulb',  'label' => 'Light Control'],
                                                ['id' => 'ac',     'icon' => 'mode_fan',   'label' => 'AC Control'],
                                            ];
                                        @endphp

                                        @foreach($vizOptions as $option)
                                            <label class="cursor-pointer group">
                                                <input wire:model="visualizationType" type="radio" value="{{ $option['id'] }}" class="peer sr-only" />
                                                <div class="border border-slate-800 rounded-xl p-3.5 flex flex-col items-center gap-2 text-center 
                                                            peer-checked:border-blue-500 peer-checked:bg-blue-500/10
                                                            hover:bg-[#1a1c26] hover:border-slate-600
                                                            transition-all duration-200">
                                                    <span class="material-symbols-outlined text-[26px] text-slate-500 
                                                                group-hover:text-slate-300 
                                                                peer-checked:text-blue-400 transition-colors">{{ $option['icon'] }}</span>
                                                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500 
                                                                peer-checked:text-blue-400 transition-colors">{{ $option['label'] }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Add Sensor Button --}}
                                <button wire:click="addSensor" type="button"
                                    class="w-full py-2.5 bg-blue-600/10 border border-blue-500/30 text-blue-400 text-sm font-medium rounded-lg hover:bg-blue-600/20 hover:border-blue-500/50 transition-all flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    Tambah Sensor ke Daftar
                                </button>
                            </div>

                            {{-- ═══ LIST OF CONFIGURED SENSORS ═══ --}}
                            @if(count($configuredSensors) > 0)
                                <div class="space-y-2">
                                    <p class="text-[10px] text-emerald-400 uppercase tracking-widest font-bold flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-sm">checklist</span>
                                        Sensor Terdaftar ({{ count($configuredSensors) }})
                                    </p>
                                    @foreach($configuredSensors as $index => $s)
                                        <div class="bg-[#161922] rounded-lg p-3 border border-slate-800 flex items-center justify-between group hover:border-slate-700 transition-colors">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-[18px] text-blue-400">
                                                        {{ match($s['visualization_type']) {
                                                            'line'   => 'show_chart',
                                                            'gauge'  => 'speed',
                                                            'light'  => 'lightbulb',
                                                            'ac'     => 'mode_fan',
                                                            default  => 'dashboard'
                                                        } }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-slate-200 text-sm font-medium">{{ ucfirst($s['measurement_type']) }}</p>
                                                    <p class="text-[10px] text-slate-500">
                                                        {{ $s['type'] }} · 
                                                        {{ $s['unit'] ? strtoupper($s['unit']) : '—' }} · 
                                                        {{ ucfirst(str_replace('_', ' ', $s['visualization_type'])) }}
                                                    </p>
                                                </div>
                                            </div>
                                            <button wire:click="removeSensor({{ $index }})"
                                                class="text-slate-600 hover:text-rose-400 transition-colors p-1 rounded hover:bg-rose-400/10 opacity-0 group-hover:opacity-100"
                                                title="Hapus sensor">
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- ─── PENDING NODES LIST ─── --}}
                        @forelse($pendingNodes as $node)
                            <div wire:key="pending-{{ $node->id }}"
                                class="flex items-center justify-between hover:bg-slate-50 dark:hover:bg-[#1a1c26] transition-colors">
                                <div class="flex items-center w-full h-16 bg-slate-100 dark:bg-[#161922] border-b border-slate-200 dark:border-slate-800/50 relative">
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-600"></div>
                                    <div class="flex-1 flex flex-col justify-center pl-6">
                                        <span
                                            class="text-[15px] font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wide">{{ $node->mac_address }}</span>
                                        <span class="text-[13px] text-slate-500 dark:text-slate-400">{{ $node->chip_type ?? 'Unknown Chip' }} ·
                                            {{ $node->firmware_ver ?? '?' }}</span>
                                    </div>
                                    <div class="flex h-full items-center">
                                        <button wire:click="configure({{ $node->id }})"
                                            class="h-full w-14 bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition-colors"
                                            title="Konfigurasi">
                                            <span class="material-symbols-outlined">settings</span>
                                        </button>
                                        <button wire:click="rejectNode({{ $node->id }})"
                                            wire:confirm="Tolak perangkat ini dari antrean?"
                                            class="h-full w-14 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors"
                                            title="Tolak">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                                <div
                                    class="w-16 h-16 rounded-full bg-slate-100 dark:bg-[#161922] flex items-center justify-center mb-4 border border-slate-200 dark:border-slate-800">
                                    <span class="material-symbols-outlined text-3xl text-slate-400 dark:text-slate-600">search</span>
                                </div>
                                <p class="text-slate-800 dark:text-slate-100 font-medium">Tidak ada permintaan baru</p>
                                <p class="text-xs text-slate-500 mt-1 max-w-[250px]">Perangkat ESP32 baru akan muncul di sini
                                    saat ditemukan oleh sistem.</p>
                            </div>
                        @endforelse
                    @endif
                </div>

                {{-- Footer Actions --}}
                @if($configMode)
                    <footer class="flex-none p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#1a1c26] flex justify-between items-center">
                        <span class="text-xs text-slate-500">
                            @if(count($configuredSensors) > 0)
                                <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ count($configuredSensors) }}</span> sensor siap
                            @else
                                Belum ada sensor
                            @endif
                        </span>
                        <div class="flex gap-3">
                            <button wire:click="backToList"
                                class="px-4 py-2 bg-transparent border border-slate-300 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm font-medium rounded transition-colors">
                                Batalkan
                            </button>
                            <button wire:click="saveNode" wire:loading.attr="disabled"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded shadow-lg shadow-blue-600/20 transition-all flex items-center gap-2 disabled:opacity-50"
                                @disabled(count($configuredSensors) === 0)>
                                <span wire:loading wire:target="saveNode" class="loading loading-spinner loading-xs"></span>
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                Setujui & Aktifkan
                            </button>
                        </div>
                    </footer>
                @else
                    <footer class="flex-none p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#1a1c26] flex justify-end gap-3">
                        <button @click="open = false"
                            class="px-4 py-2 bg-transparent border border-slate-300 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm font-medium rounded transition-colors">
                            Tutup
                        </button>
                        <button wire:click="$refresh"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded shadow-lg shadow-blue-600/20 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">refresh</span>
                            Segarkan Daftar
                        </button>
                    </footer>
                @endif
            </div>
        </div>
    </template>
</div>