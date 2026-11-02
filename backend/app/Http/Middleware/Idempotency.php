<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * منع التكرار لعمليات الكتابة القادمة من صندوق الأوفلاين (Idempotency-Key).
 * نفس المفتاح لنفس المستخدم → يُرجَع الرد المحفوظ بدل التنفيذ مرة ثانية.
 */
class Idempotency
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Idempotency-Key');
        $user = $request->user();
        if (! $key || ! $user || $request->isMethodSafe() || strlen($key) > 80) {
            return $next($request);
        }

        $hit = DB::table('idempotency_keys')->where('user_id', $user->id)->where('idem_key', $key)->first();
        if ($hit) {
            return response($hit->body ?? '', $hit->status, [
                'Content-Type' => 'application/json',
                'Idempotent-Replay' => 'true',
            ]);
        }

        $response = $next($request);

        // نحفظ النجاح وأخطاء التحقق (4xx)؛ أخطاء السيرفر 5xx تُعاد محاولتها
        if ($response->getStatusCode() < 500) {
            try {
                DB::table('idempotency_keys')->insert([
                    'user_id' => $user->id,
                    'idem_key' => $key,
                    'method' => $request->method(),
                    'path' => substr($request->path(), 0, 255),
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException $e) {
                // طلب متزامن بنفس المفتاح سبقنا — لا مشكلة
            }
        }

        return $response;
    }
}
