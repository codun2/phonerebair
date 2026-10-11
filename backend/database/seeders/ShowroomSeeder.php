<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\CarModel;
use App\Models\InventoryItem;
use App\Models\Make;
use App\Models\PriceRule;
use App\Models\RepairType;
use App\Models\Setting;
use App\Models\Technician;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShowroomSeeder extends Seeder
{
    public function run(): void
    {
        // حساب المدير
        User::updateOrCreate(
            ['email' => 'admin@mobilefix.local'],
            ['name' => 'أحمد المدير', 'password' => 'mobilefix1234', 'role' => 'admin', 'active' => true],
        );

        // إعدادات المحل
        $settings = [
            'shop_name' => 'موبايل فيكس',
            'phone' => '0599 000 000',
            'whatsapp' => '970599000000',
            'address' => 'شارع التجارة، المدينة',
            'hours' => 'السبت–الخميس · 10ص – 10م',
            'about' => 'مركز صيانة جوالات معتمد — تصليح احترافي بقطع أصلية وضمان على كل خدمة.',
        ];
        foreach ($settings as $k => $v) Setting::put($k, $v);

        // الماركات والموديلات (makes/car_models)
        $catalog = [
            'Apple' => ['iPhone 12', 'iPhone 13', 'iPhone 14', 'iPhone 15 Pro'],
            'Samsung' => ['Galaxy S23', 'Galaxy A54', 'Galaxy Note 20'],
            'Xiaomi' => ['Redmi Note 12', 'Redmi Note 13', 'Mi 11'],
            'Huawei' => ['P40', 'Mate 40', 'Nova 11'],
        ];
        $makeIds = [];
        $modelIds = [];
        foreach ($catalog as $makeName => $models) {
            $make = Make::updateOrCreate(['name' => $makeName]);
            $makeIds[$makeName] = $make->id;
            foreach ($models as $modelName) {
                $m = CarModel::updateOrCreate(['make_id' => $make->id, 'name' => $modelName]);
                $modelIds["$makeName|$modelName"] = $m->id;
            }
        }

        // أنواع الأعطال
        $types = [
            ['name' => 'تغيير شاشة', 'icon' => 'ti-device-mobile-charging'],
            ['name' => 'بطارية', 'icon' => 'ti-battery-2'],
            ['name' => 'منفذ شحن', 'icon' => 'ti-plug'],
            ['name' => 'سوفتوير', 'icon' => 'ti-cpu'],
            ['name' => 'كاميرا', 'icon' => 'ti-camera'],
            ['name' => 'أضرار مياه', 'icon' => 'ti-droplet'],
        ];
        $typeIds = [];
        foreach ($types as $i => $t) {
            $rt = RepairType::updateOrCreate(
                ['name' => $t['name']],
                ['icon' => $t['icon'], 'sort_order' => $i, 'active' => true],
            );
            $typeIds[$t['name']] = $rt->id;
        }

        // جدول الأسعار (يغذّي الحاسبة) — [ماركة, موديل, عطل, سعر, دقائق, ضمان]
        $prices = [
            ['Apple', 'iPhone 12', 'تغيير شاشة', 390, 45, 6],
            ['Apple', 'iPhone 12', 'بطارية', 220, 30, 6],
            ['Apple', 'iPhone 14', 'تغيير شاشة', 450, 45, 6],
            ['Apple', 'iPhone 14', 'بطارية', 250, 30, 6],
            ['Apple', 'iPhone 14', 'منفذ شحن', 180, 40, 3],
            ['Apple', 'iPhone 15 Pro', 'تغيير شاشة', 790, 60, 6],
            ['Apple', 'iPhone 15 Pro', 'كاميرا', 650, 60, 3],
            ['Samsung', 'Galaxy S23', 'تغيير شاشة', 520, 60, 3],
            ['Samsung', 'Galaxy S23', 'بطارية', 220, 40, 3],
            ['Samsung', 'Galaxy A54', 'تغيير شاشة', 340, 60, 3],
            ['Xiaomi', 'Redmi Note 12', 'تغيير شاشة', 260, 60, 3],
            ['Xiaomi', 'Redmi Note 12', 'سوفتوير', 100, 60, 1],
            ['Huawei', 'P40', 'تغيير شاشة', 320, 60, 3],
            ['Huawei', 'P40', 'بطارية', 180, 40, 3],
        ];
        foreach ($prices as $p) {
            [$mk, $md, $ty, $price, $dur, $warr] = $p;
            PriceRule::updateOrCreate(
                [
                    'make_id' => $makeIds[$mk],
                    'car_model_id' => $modelIds["$mk|$md"] ?? null,
                    'repair_type_id' => $typeIds[$ty],
                ],
                ['price' => $price, 'duration_minutes' => $dur, 'warranty_months' => $warr],
            );
        }

        // الفنيون
        $techs = [
            ['name' => 'سامي حسن', 'specialty' => 'فني أول'],
            ['name' => 'خالد ناصر', 'specialty' => 'فني'],
            ['name' => 'يوسف عمر', 'specialty' => 'فني'],
        ];
        $techIds = [];
        foreach ($techs as $t) {
            $tech = Technician::updateOrCreate(['name' => $t['name']], ['specialty' => $t['specialty'], 'active' => true]);
            $techIds[$t['name']] = $tech->id;
        }

        // المخزون
        $items = [
            ['name' => 'شاشة OLED', 'sku' => 'SCR-IP14', 'compatible' => 'iPhone 14', 'qty' => 12, 'cost' => 220, 'low_threshold' => 4],
            ['name' => 'بطارية أصلية', 'sku' => 'BAT-S23', 'compatible' => 'Galaxy S23', 'qty' => 3, 'cost' => 90, 'low_threshold' => 5],
            ['name' => 'منفذ شحن', 'sku' => 'CHG-IP13', 'compatible' => 'iPhone 13', 'qty' => 8, 'cost' => 40, 'low_threshold' => 5],
            ['name' => 'شاشة LCD', 'sku' => 'SCR-RN12', 'compatible' => 'Redmi Note 12', 'qty' => 0, 'cost' => 65, 'low_threshold' => 3],
            ['name' => 'كفر حماية شفاف', 'sku' => 'ACC-CLR', 'compatible' => 'عام', 'qty' => 54, 'cost' => 8, 'low_threshold' => 10],
        ];
        foreach ($items as $it) {
            InventoryItem::updateOrCreate(['sku' => $it['sku']], $it);
        }

        // تذاكر تجريبية
        $tickets = [
            ['محمد خالد', '0551234567', 'Apple', 'iPhone 14', 'تغيير شاشة', 450, 'in_repair', 'سامي حسن'],
            ['نورة العتيبي', '0559876543', 'Samsung', 'Galaxy S23', 'بطارية', 220, 'ready', 'خالد ناصر'],
            ['فيصل الدوسري', '0501112223', 'Apple', 'iPhone 13', 'منفذ شحن', 180, 'awaiting_approval', null],
            ['سارة القحطاني', '0533334445', 'Xiaomi', 'Redmi Note 12', 'سوفتوير', 100, 'received', 'سامي حسن'],
            ['عبدالله الشمري', '0544445556', 'Apple', 'iPhone 12', 'تغيير شاشة', 390, 'delivered', 'خالد ناصر'],
        ];
        foreach ($tickets as $t) {
            [$name, $phone, $mk, $md, $ty, $price, $status, $tech] = $t;
            if (Ticket::where('customer_phone', $phone)->where('model', $md)->exists()) continue;
            $ticket = Ticket::create([
                'ticket_no' => Ticket::nextNumber(),
                'customer_name' => $name,
                'customer_phone' => $phone,
                'make' => $mk,
                'model' => $md,
                'repair_type_id' => $typeIds[$ty] ?? null,
                'price' => $price,
                'warranty_months' => 6,
                'status' => $status,
                'technician_id' => $tech ? $techIds[$tech] : null,
                'description' => 'جهاز يحتاج ' . $ty . '.',
            ]);
            TicketEvent::create(['ticket_id' => $ticket->id, 'status' => 'received', 'note' => 'تم استلام الجهاز']);
            if ($status !== 'received') {
                TicketEvent::create(['ticket_id' => $ticket->id, 'status' => $status, 'note' => Ticket::STATUS_LABELS[$status] ?? $status]);
            }
        }

        // حجوزات تجريبية من الموقع
        Booking::firstOrCreate(
            ['phone' => '0567778889', 'model' => 'iPhone 15 Pro'],
            ['name' => 'ريهام سالم', 'make' => 'Apple', 'repair_type' => 'كاميرا', 'service_type' => 'visit', 'preferred_time' => 'اليوم مساءً', 'status' => 'new'],
        );

        $this->command->info('  → seeded phone-repair domain (makes, types, prices, techs, inventory, tickets)');
    }
}
