<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' . config('app.name') : config('app.name') }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />

    <script>
        const theme = localStorage.getItem('theme') || 'system';
        const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (isDark) {
            document.documentElement.classList.add('dark');
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>

    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
        }

        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 dark:bg-[#11131b] text-slate-800 dark:text-slate-200 font-body-md text-body-md antialiased overflow-x-hidden min-h-screen transition-colors duration-300">
    <!-- SideNavBar -->
    <aside class="bg-[#f4f7fb] dark:bg-slate-950 font-inter text-sm fixed left-0 top-0 h-screen w-[260px] border-r border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl flex flex-col z-50 transition-colors duration-300">
        {{-- Profile Header --}}
        <div class="px-6 py-8 border-b border-slate-200 dark:border-slate-800/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center overflow-hidden border border-slate-200 dark:border-slate-700">
                    <img alt="Operator Profile" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA2RrwGWq7NSd-KqOc0WNAOZCNtIUM4a5aJmMsQwXAKJr4YR7sku_DPOCDQswVejdVUsKasjrEDwQJtJj1yMS_PZQiJtlQlDsUFJvOnH6R_4USY8B7cLwCGHfO99jXJzZOqwOjaegiAcT4XGjwq1vH_EOl2ZQiB2lVOnkur66ntM0c69plIYl2oWxw9d3bJXG-Nm_TKf1DXKd77ssqmNeAnswkfk0HXWH-zkcJXXiOdOjLc-DlSFXRlgXezwMWc1ZsTP7WdbH-FUQ"/>
                </div>
                <div>
                    <h2 class="text-slate-800 dark:text-slate-200 font-semibold tracking-tight">{{ auth()->user()->name ?? 'BNSMS Admin' }}</h2>
                    <p class="text-slate-500 dark:text-slate-500 text-xs capitalize">{{ auth()->user()->roles->pluck('name')->first() ?? 'User' }}</p>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-400/90 bg-emerald-50 dark:bg-emerald-400/10 px-3 py-1.5 rounded-full w-fit border border-emerald-100 dark:border-emerald-400/20">
                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></div>
                System Status: Online
            </div>
        </div>

        {{-- Main Nav --}}
        <nav class="flex-1 py-4 flex flex-col gap-2 px-4 overflow-y-auto">
            @role('super_admin')
            <a class="{{ request()->routeIs('admin.client') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('admin.client') }}" wire:navigate>
                <span class="material-symbols-outlined">layers</span>
                Buildings
            </a>
            <a class="{{ request()->routeIs('admin.rooms') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('admin.rooms') }}" wire:navigate>
                <span class="material-symbols-outlined">meeting_room</span>
                Rooms
            </a>
            <a class="{{ request()->routeIs('admin.maintenance') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('admin.maintenance') }}" wire:navigate>
                <span class="material-symbols-outlined">router</span>
                Maintenance
            </a>
            <a class="{{ request()->routeIs('admin.users') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('admin.users') }}" wire:navigate>
                <span class="material-symbols-outlined">group</span>
                User Management
            </a>
            @endrole

            @role('client')
            <a class="{{ request()->routeIs('client') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('client') }}" wire:navigate>
                <span class="material-symbols-outlined">dashboard</span>
                Dashboard
            </a>
            <a class="{{ request()->routeIs('client.rooms') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('client.rooms') }}" wire:navigate>
                <span class="material-symbols-outlined">meeting_room</span>
                Rooms
            </a>
            <a class="{{ request()->routeIs('client.staff') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('client.staff') }}" wire:navigate>
                <span class="material-symbols-outlined">group</span>
                Staff
            </a>
            @endrole

            @role('operator')
            <a class="{{ request()->routeIs('operator') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('operator') }}" wire:navigate>
                <span class="material-symbols-outlined">dashboard</span>
                Dashboard
            </a>
            <a class="{{ request()->routeIs('operator.requests') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('operator.requests') }}" wire:navigate>
                <span class="material-symbols-outlined">confirmation_number</span>
                Requests
            </a>
            @endrole

            @role('maintenance')
            <a class="{{ request()->routeIs('maintenance') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('maintenance') }}" wire:navigate>
                <span class="material-symbols-outlined">dashboard</span>
                Dashboard
            </a>
            <a class="{{ request()->routeIs('maintenance.nodes') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('maintenance.nodes') }}" wire:navigate>
                <span class="material-symbols-outlined">hub</span>
                Nodes
            </a>
            <a class="{{ request()->routeIs('maintenance.diagnostics') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('maintenance.diagnostics') }}" wire:navigate>
                <span class="material-symbols-outlined">troubleshoot</span>
                Diagnostics
            </a>
            @endrole

            @role('viewer')
            <a class="{{ request()->routeIs('viewer') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('viewer') }}" wire:navigate>
                <span class="material-symbols-outlined">dashboard</span>
                Dashboard
            </a>
            <a class="{{ request()->routeIs('viewer.request') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('viewer.request') }}" wire:navigate>
                <span class="material-symbols-outlined">support_agent</span>
                Requests
            </a>
            @endrole

            {{-- Dashboard link for all roles --}}
            <a class="{{ request()->routeIs('dashboard') ? 'bg-[#dbeafe] dark:bg-blue-600/10 text-blue-600 dark:text-blue-500 font-medium' : 'text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900/50 hover:text-slate-900 dark:hover:text-slate-100' }} flex items-center gap-3 px-4 py-3 duration-200 rounded-lg" href="{{ route('dashboard') }}" wire:navigate>
                <span class="material-symbols-outlined">monitoring</span>
                General Dashboard
            </a>
        </nav>

        {{-- Footer Nav --}}
        <div class="px-4 pb-4 border-t border-slate-200 dark:border-slate-800/50 pt-4 flex flex-col gap-1">
            <a class="flex items-center gap-3 text-rose-500 dark:text-rose-400/80 px-4 py-3 hover:bg-white dark:hover:bg-slate-900/50 hover:text-rose-600 dark:hover:text-rose-400 duration-200 cursor-pointer rounded-lg" href="{{ route('logout') }}" no-wire-navigate>
                <span class="material-symbols-outlined">logout</span>
                Sign Out
            </a>
        </div>
    </aside>

    <!-- TopNavBar -->
    <header class="bg-white/90 dark:bg-slate-950/80 backdrop-blur-md font-inter text-sm antialiased docked full-width top-0 border-b border-slate-200 dark:border-slate-800 shadow-sm shadow-slate-200/50 dark:shadow-blue-900/10 flex justify-between items-center w-full px-6 h-14 ml-[260px] max-w-[calc(100%-260px)] fixed z-40 transition-colors duration-300">
        <div class="flex items-center gap-4">
            <button onclick="history.back()" class="p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900/50 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer active:scale-95 duration-150 rounded-full flex items-center justify-center" title="Kembali">
                <span class="material-symbols-outlined">arrow_back</span>
            </button>
            <div class="text-lg font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-500">domain</span>
                BNSMS SmartBuilding
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{-- Theme Toggle --}}
            <x-theme-toggle class="p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900/50 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer active:scale-95 duration-150 rounded-full" />

            <button class="p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900/50 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer active:scale-95 duration-150 rounded-full">
                <span class="material-symbols-outlined">settings</span>
            </button>
            <button class="p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-900/50 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer active:scale-95 duration-150 rounded-full">
                <span class="material-symbols-outlined">account_circle</span>
            </button>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="ml-[260px] pt-16 min-h-screen bg-[#f8fafc] dark:bg-[#11131b] p-margin-page transition-colors duration-300">
        {{ $slot }}
    </main>

    {{-- TOAST area --}}
    <x-toast />
</body>

</html>