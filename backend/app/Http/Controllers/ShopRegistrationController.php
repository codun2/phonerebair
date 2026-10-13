<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Services\ShopProvisioner;
use Illuminate\Http\Request;

/** تسجيل ذاتي لمحل جديد — ينشأ بحالة "بانتظار الموافقة". */
class ShopRegistrationController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'shop_name' => 'required|string|max:120',
            'owner_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:160|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $shop = Shop::create([
            'name' => $data['shop_name'],
            'slug' => Shop::makeSlug($data['shop_name']),
            'owner_name' => $data['owner_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'status' => 'pending',
        ]);

        User::create([
            'shop_id' => $shop->id,
            'name' => $data['owner_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'admin',
            'active' => true,
        ]);

        ShopProvisioner::seedDefaults($shop);

        return response()->json([
            'message' => 'تم استلام طلبك! سيتم تفعيل محلك بعد مراجعة الإدارة، وسنبلّغك عند التفعيل.',
        ], 201);
    }
}
