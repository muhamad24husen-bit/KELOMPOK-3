<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: EnsureTenantContext
 *
 * Berjalan setelah auth middleware. Resolve client_id dari user
 * yang login dan simpan ke session + service container agar
 * TenantScope tidak perlu query relasi di setiap request.
 *
 * super_admin: skip (lihat semua data, tidak ada tenant filter).
 */
class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->hasRole('super_admin')) {
            // Ambil dari session jika sudah ada, atau resolve dari relasi
            $clientId = session('tenant_client_id');

            if (is_null($clientId)) {
                $clientId = auth()->user()->getTenantClientId();
                session(['tenant_client_id' => $clientId]);
            }

            // Bind ke service container agar TenantScope bisa akses tanpa query
            app()->instance('tenant.client_id', $clientId);
        }

        return $next($request);
    }
}
