<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ShopScope implements Scope
{
    /**
     * يفلتر كل الاستعلامات على المحل الحالي.
     * - لو فيه محل حالي مربوط (currentShopId) => يفلتر عليه.
     * - لو ما فيه (super-admin أو سياق إداري) => بدون فلترة (يرى الكل).
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('currentShopId') && app('currentShopId')) {
            $builder->where($model->getTable() . '.shop_id', app('currentShopId'));
        }
    }
}
