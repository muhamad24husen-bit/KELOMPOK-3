<?php

use Illuminate\Support\Facades\Auth;

// ── Public ─────────────────────────────────────────────────────
Route::livewire('/', 'pages::auth.login')->name('login');

Route::get('/logout', function () {
    session()->forget('tenant_client_id'); // Clear tenant context on logout
    Auth::logout();
    return redirect()->route('login');
})->name('logout');

// ── Dashboard (all authenticated users) ───────────────────────
Route::middleware(['auth'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard.idx')->name('dashboard');
});

// ── Super Admin Panel ──────────────────────────────────────────
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->group(function () {
    Route::livewire('/',                    'pages::admin.idx')->name('admin');
    Route::livewire('/clients',             'pages::admin.client.idx')->name('admin.client');
    Route::livewire('/rooms',               'pages::admin.room.idx')->name('admin.rooms');
    Route::livewire('/rooms/{room}/monitor', 'pages::admin.room.monitor')->name('admin.rooms.monitor');
    Route::livewire('/users',               'pages::admin.user.idx')->name('admin.users');
    Route::livewire('/maintenance',         'pages::admin.maintenance.idx')->name('admin.maintenance');
});

// ── Client Panel (Pemilik Gedung) ─────────────────────────────
Route::middleware(['auth', 'role:client'])->prefix('client')->group(function () {
    Route::livewire('/',       'pages::client.idx')->name('client');
    Route::livewire('/rooms',  'pages::client.room.idx')->name('client.rooms');
    Route::livewire('/staff',  'pages::client.staff.idx')->name('client.staff');
});

// ── Operator Panel ─────────────────────────────────────────────
Route::middleware(['auth', 'role:operator'])->prefix('ops')->group(function () {
    Route::livewire('/',                'pages::operator.idx')->name('operator');
    Route::livewire('/monitor/{room}',  'pages::operator.monitor')->name('operator.monitor');
    Route::livewire('/requests',        'pages::operator.requests')->name('operator.requests');
});

// ── Maintenance Panel ──────────────────────────────────────────
Route::middleware(['auth', 'role:maintenance'])->prefix('tech')->group(function () {
    Route::livewire('/',            'pages::maintenance.idx')->name('maintenance');
    Route::livewire('/nodes',       'pages::maintenance.nodes')->name('maintenance.nodes');
    Route::livewire('/diagnostics', 'pages::maintenance.diagnostics')->name('maintenance.diagnostics');
});

// ── Viewer Panel ───────────────────────────────────────────────
Route::middleware(['auth', 'role:viewer'])->prefix('view')->group(function () {
    Route::livewire('/',        'pages::viewer.idx')->name('viewer');
    Route::livewire('/request', 'pages::viewer.request')->name('viewer.request');
});
