@props([
    'action' => 'exportPdf',
    'label' => 'Ekspor',
    'icon' => 'download'
])

<button 
    wire:click="{{ $action }}"
    wire:loading.attr="disabled"
    class="cursor-pointer bg-[#2D60FF] hover:bg-blue-600 disabled:bg-blue-400 text-white px-5 py-2.5 rounded-lg font-label-md text-label-md flex items-center gap-3 transition-all shadow-lg shadow-blue-900/20 active:scale-95 border border-blue-500/50 relative"
>
    <span wire:loading.remove wire:target="{{ $action }}" class="material-symbols-outlined text-[20px]">
        {{ $icon }}
    </span>
    
    <span wire:loading wire:target="{{ $action }}" class="loading loading-spinner loading-xs"></span>
    
    <span wire:loading.remove wire:target="{{ $action }}">
        {{ $label }}
    </span>
    
    <span wire:loading wire:target="{{ $action }}">Memproses...</span>
</button>