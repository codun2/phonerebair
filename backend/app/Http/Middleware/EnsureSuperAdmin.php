<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** يسمح فقط للمدير العام للمنصّة (super-admin). */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || $request->user()->role !== 'super_admin') {
            return response()->json(['message' => 'غير مصرّح'], 403);
        }

        return $next($request);
    }
}
