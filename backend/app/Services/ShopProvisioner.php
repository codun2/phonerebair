<?php

namespace App\Services;

use App\Models\RepairType;
use App\Models\Setting;
use App\Models\Shop;

/** يهيّئ محلًا جديدًا بإعدادات وأنواع أعطال افتراضية (الكتالوج ماركات/موديلات مشترك عالميًا). */
class ShopProvisioner
{
    public static function seedDefaults(Shop $shop): void
    {
        app()->instance('currentShopId', $shop->id);

        $defaults = [
            'shop_name' => $shop->name,
            'phone' => $shop->phone,
            'whatsapp' => preg_replace('/\D/', '', (string) $shop->phone),
            'address' => $shop->address,
            'hours' => 'السبت–الخميس · 10ص – 10م',
            'about' => 'مركز صيانة جوالات — تصليح احترافي بقطع جيدة وضمان على كل خدمة.',
        ];
        foreach ($defaults as $k => $v) {
            if (Setting::where('key', $k)->doesntExist()) {
                Setting::put($k, $v ?? '');
            }
        }

        $types = [
            ['تغيير شاشة', 'ti-device-mobile-charging'],
            ['بطارية', 'ti-battery-2'],
            ['منفذ شحن', 'ti-plug'],
            ['سوفتوير', 'ti-cpu'],
            ['كاميرا', 'ti-camera'],
            ['أضرار مياه', 'ti-droplet'],
        ];
        foreach ($types as $i => [$name, $icon]) {
            RepairType::firstOrCreate(['name' => $name], ['icon' => $icon, 'sort_order' => $i, 'active' => true]);
        }

        app()->forgetInstance('currentShopId');
    }
}
