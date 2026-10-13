<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * يضمن وجود حساب مدير المنصّة (super-admin) — يعمل عند النشر تلقائيًا
 * لأن الـseeders لا تُشغَّل في النشر. غيّر كلمة المرور بعد أول دخول.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('users')->where('role', 'super_admin')->exists();
        if ($exists) {
            return;
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'super@admin.local'],
            [
                'name' => 'المدير العام',
                'password' => Hash::make('super1234'),
                'role' => 'super_admin',
                'active' => true,
                'shop_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'super@admin.local')->where('role', 'super_admin')->delete();
    }
};
