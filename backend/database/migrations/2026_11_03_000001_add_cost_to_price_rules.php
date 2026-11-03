<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تسعير قائم على التكلفة:
 * تكلفة داخلية للمحل (قطع + أجرة الفني) لكل قاعدة سعر.
 * يُشتقّ منها سعر البيع = التكلفة × (1 + هامش الربح%). التكلفة لا تظهر للزبون أبداً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_rules', function (Blueprint $table) {
            $table->decimal('cost', 10, 2)->nullable()->after('repair_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('price_rules', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
};
