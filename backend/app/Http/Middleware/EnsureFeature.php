<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** يسمح بالوصول فقط إذا كانت الميزة مفعّلة لمحل المستخدم. استخدام: feature:inventory */
class EnsureFeature
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = $request->user();
        // super-admin يتجاوز، والمحل لازم تكون الميزة مفعّلة له
        if ($user && ! $user->isSuperAdmin()) {
            $shop = $user->shop;
            if (! $shop || ! $shop->hasFeature($feature)) {
                return response()->json(['message' => 'هذه الميزة غير مفعّلة لمحلك'], 403);
            }
        }

        return $next($request);
    }
}
