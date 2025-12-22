<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {

        Log::withContext([
            'url' => $request->fullUrl(),
            'user_id' => $request->user()->id ?? null,
            'user_email' => $request->user()->email ?? null,
            'user_name' => $request->user()->name ?? null,
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $request->all(),
        ]);

        return $next($request);
    }
}
