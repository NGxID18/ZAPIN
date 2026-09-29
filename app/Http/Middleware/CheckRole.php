<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!session()->has('user_role')) {
            return redirect()->route('login')
                ->with('error', 'Silakan masuk terlebih dahulu.');
        }

        if (!in_array(session('user_role'), $roles)) {
            return redirect()->route('dashboard')
                ->with('error', 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses halaman tersebut.');
        }

        return $next($request);
    }
}
