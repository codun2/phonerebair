<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // الباقات
        $basic = Plan::updateOrCreate(['slug' => 'basic'], [
            'name' => 'الأساسية', 'price' => 99, 'duration_days' => 30,
            'description' => 'مناسبة لمحل واحد — تذاكر ومخزون وحجوزات بلا حدود.',
            'max_technicians' => 3, 'is_active' => true, 'sort_order' => 1,
        ]);
        Plan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'الاحترافية', 'price' => 199, 'duration_days' => 30,
            'description' => 'فنيون غير محدودين + كل المزايا.',
            'max_technicians' => null, 'is_active' => true, 'sort_order' => 2,
        ]);

        // المدير العام للمنصّة (super-admin) — بلا محل
        User::updateOrCreate(
            ['email' => 'super@admin.local'],
            ['name' => 'المدير العام', 'password' => 'super1234', 'role' => 'super_admin', 'active' => true, 'shop_id' => null],
        );

        // محل تجريبي (المحل الرئيسي) + بياناته
        $shop = Shop::updateOrCreate(['slug' => 'main'], [
            'name' => 'موبايل فيكس', 'owner_name' => 'أحمد المدير',
            'phone' => '0599 000 000', 'status' => 'active', 'plan_id' => $basic->id,
            'features' => array_keys(Shop::AVAILABLE_FEATURES),   // المحل التجريبي يحصل على كل المميزات
            'subscription_ends_at' => now()->addYears(5),
        ]);

        // اربط المحل كمستأجر حالي ثم ازرع بياناته
        app()->instance('currentShopId', $shop->id);
        $this->call(ShowroomSeeder::class);
        app()->forgetInstance('currentShopId');
    }
}
