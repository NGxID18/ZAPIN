<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyZapinApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secretKey = (string) config('zapin.api_key');
        $authKey = (string) ($request->header('X-ZAPIN-KEY') ?: $request->input('api_key', ''));

        if (empty($secretKey) || empty($authKey) || !hash_equals($secretKey, $authKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Kunci API ZAPIN tidak valid.'
            ], 401);
        }

        return $next($request);
    }
}

