<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'repair_types', 'price_rules', 'technicians', 'tickets',
        'ticket_media', 'ticket_events', 'inventory_items', 'bookings', 'settings',
    ];

    public function up(): void
    {
        // لا تنشئ محلًا افتراضيًا إلا إذا فيه بيانات قديمة فعلًا
        $hasLegacy = DB::table('tickets')->exists()
            || DB::table('settings')->exists()
            || DB::table('repair_types')->exists()
            || DB::table('users')->exists();

        if (! $hasLegacy) {
            return;
        }

        $planId = DB::table('plans')->value('id');
        if (! $planId) {
            $planId = DB::table('plans')->insertGetId([
                'name' => 'أساسي', 'slug' => 'basic', 'price' => 0,
                'duration_days' => 30, 'description' => 'الباقة الأساسية',
                'is_active' => true, 'sort_order' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $name = DB::table('settings')->where('key', 'shop_name')->value('value') ?: 'المحل الرئيسي';

        $shopId = DB::table('shops')->insertGetId([
            'name' => $name,
            'slug' => 'main',
            'status' => 'active',
            'plan_id' => $planId,
            'subscription_ends_at' => now()->addYears(5),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($this->tables as $t) {
            DB::table($t)->whereNull('shop_id')->update(['shop_id' => $shopId]);
        }

        // كل المستخدمين الحاليين يصيرون أدمِن لهذا المحل
        DB::table('users')->whereNull('shop_id')->update(['shop_id' => $shopId]);
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            DB::table($t)->update(['shop_id' => null]);
        }
        DB::table('users')->update(['shop_id' => null]);
        DB::table('shops')->where('slug', 'main')->delete();
    }
};
