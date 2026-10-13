<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة']);
        }
        if (! $user->active) {
            throw ValidationException::withMessages(['email' => 'تم تعطيل هذا الحساب']);
        }

        // بوابة اشتراك المحل (لغير المدير العام)
        if (! $user->isSuperAdmin()) {
            $shop = $user->shop;
            if (! $shop) {
                throw ValidationException::withMessages(['email' => 'لا يوجد محل مرتبط بحسابك']);
            }
            $state = $shop->accessState();
            if ($state !== 'active') {
                $msg = match ($state) {
                    'pending' => 'محلك قيد المراجعة من الإدارة، سيتم تفعيله قريبًا',
                    'suspended' => 'تم إيقاف محلك مؤقتًا، تواصل مع الدعم',
                    'expired' => 'انتهى اشتراك محلك، يرجى التجديد للمتابعة',
                    default => 'الحساب غير متاح حاليًا',
                };
                throw ValidationException::withMessages(['email' => $msg]);
            }
        }

        return response()->json([
            'user' => $user->load('shop'),
            'token' => $user->createToken('showroom')->plainTextToken,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()->load('shop')]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }
}
