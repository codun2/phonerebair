<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** يربط المحل الحالي من المستخدم المسجّل (لغير الـ super-admin). */
class SetTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->role !== 'super_admin' && $user->shop_id) {
            app()->instance('currentShopId', $user->shop_id);
        }

        return $next($request);
    }
}
