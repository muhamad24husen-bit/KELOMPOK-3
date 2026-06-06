<?php

use Livewire\Component;

use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use Livewire\Attributes\Layout;

#la
new #[Layout('layouts.auth')] class extends Component
{
    use Toast;
    public $email;
    public $password;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];
    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            return redirect()->route('dashboard');
        } else {
            $this->error('Email atau password salah', position: 'toast-top toast-center');
        }
    }
};
?>

<div>
    <div class="w-full max-w-md mx-auto">
        <!-- Logo Header -->
        <div class="flex flex-col items-center mb-unit-xl">
            <div class="h-16 w-16 bg-blue-600 dark:bg-primary-container rounded-lg flex items-center justify-center mb-unit-md shadow-lg shadow-blue-600/20 dark:shadow-black/20">
                <span class="material-symbols-outlined text-[32px] text-white dark:text-on-primary-container" style="font-variation-settings: 'FILL' 1;">domain</span>
            </div>
            <h1 class="font-display text-display text-slate-800 dark:text-on-background tracking-tighter uppercase">BNSMS</h1>
            <p class="font-body-md text-body-md text-slate-500 dark:text-on-surface-variant mt-unit-xs">Building & Node Sensor Management System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white dark:bg-surface-container rounded-xl shadow-xl shadow-slate-200/50 dark:shadow-2xl dark:shadow-black/40 border border-slate-200 dark:border-surface-container-highest p-unit-xl transition-colors duration-300">
            <h2 class="font-h3 text-h3 text-slate-800 dark:text-on-surface mb-unit-lg">Sign In</h2>
            
            <form wire:submit="login" class="space-y-unit-lg">
                <!-- Email Input -->
                <div>
                    <label for="email" class="block font-label-md text-label-md text-slate-600 dark:text-on-surface-variant mb-unit-xs">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-slate-400 dark:text-outline text-[20px]">mail</span>
                        </div>
                        <input type="email" id="email" wire:model="email" placeholder="admin@bnsms.local" class="block w-full pl-10 pr-3 py-2 border border-slate-300 dark:border-outline-variant rounded bg-white dark:bg-surface text-slate-800 dark:text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-primary-container focus:border-blue-600 dark:focus:border-primary-container placeholder-slate-400 dark:placeholder-outline transition-colors">
                    </div>
                    @error('email') <span class="text-error font-label-sm text-label-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Password Input -->
                <div>
                    <div class="flex items-center justify-between mb-unit-xs">
                        <label for="password" class="block font-label-md text-label-md text-slate-600 dark:text-on-surface-variant">Password</label>
                        <a href="#" class="font-label-sm text-label-sm text-blue-600 dark:text-primary hover:text-blue-700 dark:hover:text-primary-fixed transition-colors">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-slate-400 dark:text-outline text-[20px]">lock</span>
                        </div>
                        <input type="password" id="password" wire:model="password" placeholder="••••••••" class="block w-full pl-10 pr-3 py-2 border border-slate-300 dark:border-outline-variant rounded bg-white dark:bg-surface text-slate-800 dark:text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-primary-container focus:border-blue-600 dark:focus:border-primary-container placeholder-slate-400 dark:placeholder-outline transition-colors">
                    </div>
                    @error('password') <span class="text-error font-label-sm text-label-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input type="checkbox" id="remember-me" name="remember-me" class="h-4 w-4 text-blue-600 dark:text-primary-container focus:ring-blue-600 dark:focus:ring-primary-container border-slate-300 dark:border-outline-variant rounded DEFAULT bg-white dark:bg-surface">
                    <label for="remember-me" class="ml-2 block font-body-sm text-body-sm text-slate-600 dark:text-on-surface-variant">
                        Remember me for 30 days
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded shadow-sm font-label-md text-label-md text-white dark:text-on-primary-container bg-blue-600 dark:bg-primary-container hover:bg-blue-700 dark:hover:bg-inverse-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 dark:focus:ring-primary-container transition-colors focus:ring-offset-white dark:focus:ring-offset-background">
                    Login to Central
                </button>
            </form>
        </div>

        <!-- Footer / Theme Toggle -->
        <div class="mt-unit-xl flex flex-col items-center justify-center space-y-unit-md text-center">
            <div class="flex items-center space-x-2 bg-slate-100 dark:bg-surface-container-high rounded-full p-1 border border-slate-200 dark:border-surface-container-highest transition-colors duration-300">
                <button aria-label="Light mode" class="flex items-center justify-center p-2 rounded-full text-blue-600 dark:text-on-surface-variant hover:text-blue-700 dark:hover:text-on-surface bg-white dark:bg-transparent shadow-sm dark:shadow-none transition-colors">
                    <span class="material-symbols-outlined text-[20px]">light_mode</span>
                </button>
                <button aria-label="Dark mode" class="flex items-center justify-center p-2 rounded-full text-slate-500 dark:text-primary hover:text-slate-700 dark:hover:text-primary bg-transparent dark:bg-surface-container-highest shadow-none dark:shadow-sm transition-colors">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">dark_mode</span>
                </button>
                <button aria-label="System theme" class="flex items-center justify-center p-2 rounded-full text-slate-500 dark:text-on-surface-variant hover:text-slate-700 dark:hover:text-on-surface bg-transparent transition-colors">
                    <span class="material-symbols-outlined text-[20px]">desktop_windows</span>
                </button>
            </div>
            <p class="font-label-sm text-label-sm text-slate-400 dark:text-outline">
                &copy; 2024 BNSMS Systems. Authorized Personnel Only.
            </p>
        </div>
    </div>
</div>
