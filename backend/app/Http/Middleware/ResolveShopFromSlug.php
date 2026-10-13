<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;

/** يحلّ المحل من الـ slug في المسار العام ويربطه كمستأجر حالي. */
class ResolveShopFromSlug
{
    public function handle(Request $request, Closure $next)
    {
        $slug = $request->route('shopSlug');
        $shop = Shop::where('slug', $slug)->first();

        if (! $shop) {
            return response()->json(['message' => 'المحل غير موجود'], 404);
        }
        if ($shop->accessState() !== 'active') {
            return response()->json(['message' => 'هذا المحل غير متاح حاليًا'], 403);
        }

        app()->instance('currentShopId', $shop->id);
        app()->instance('currentShop', $shop);

        return $next($request);
    }
}
