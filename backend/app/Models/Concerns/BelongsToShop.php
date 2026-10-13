<?php

namespace App\Models\Concerns;

use App\Models\Scopes\ShopScope;
use App\Models\Shop;

trait BelongsToShop
{
    public static function bootBelongsToShop(): void
    {
        static::addGlobalScope(new ShopScope);

        // عند الإنشاء: عبّئ shop_id تلقائيًا من المحل الحالي إن لم يُحدَّد
        static::creating(function ($model) {
            if (empty($model->shop_id) && app()->bound('currentShopId') && app('currentShopId')) {
                $model->shop_id = app('currentShopId');
            }
        });
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /** استعلام بدون فلترة المحل (لاستخدام super-admin) */
    public function scopeAllShops($query)
    {
        return $query->withoutGlobalScope(ShopScope::class);
    }
}
