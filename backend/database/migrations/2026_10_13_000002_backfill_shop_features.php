<?php

use App\Models\Shop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * المحلات التي أُنشئت قبل نظام المميزات (features = NULL) تحصل على كل المميزات،
 * حتى لا تفقد وظائف كانت تعمل (مثل المخزون). المحلات الجديدة تبقى بلا مميزات
 * حتى يمنحها super-admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        $all = json_encode(array_keys(Shop::AVAILABLE_FEATURES));
        DB::table('shops')->whereNull('features')->update(['features' => $all]);
    }

    public function down(): void
    {
        // لا تراجع (لا نريد إزالة مميزات مُنحت)
    }
};
