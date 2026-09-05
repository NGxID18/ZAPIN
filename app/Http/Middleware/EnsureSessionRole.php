<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('user_role')) {
            return redirect()->route('login')
                ->with('error', 'Silakan pilih peran login terlebih dahulu untuk mengakses sistem ZAPIN.');
        }

        return $next($request);
    }
}
