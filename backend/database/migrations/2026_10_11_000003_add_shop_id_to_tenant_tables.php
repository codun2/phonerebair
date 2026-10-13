<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // الجداول المعزولة لكل محل (الكتالوج makes/car_models/brands يبقى مشتركًا عالميًا)
    private array $tables = [
        'repair_types', 'price_rules', 'technicians', 'tickets',
        'ticket_media', 'ticket_events', 'inventory_items', 'bookings', 'settings',
    ];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) use ($t) {
                $table->foreignId('shop_id')->nullable()->after('id')
                    ->constrained('shops')->cascadeOnDelete();
            });
        }

        // قيود التفرّد تصير لكل محل بدل عالميًا
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['ticket_no']);
            $table->unique(['shop_id', 'ticket_no']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['shop_id', 'key']);
        });

        Schema::table('price_rules', function (Blueprint $table) {
            // make_id FK كان يعتمد على price_rule_unique (العمود الأيسر) — نوفّر فهرسًا بديلًا قبل الإسقاط
            $table->index('make_id', 'price_rules_make_id_idx');
            $table->dropUnique('price_rule_unique');
            $table->unique(['shop_id', 'make_id', 'car_model_id', 'repair_type_id'], 'price_rule_shop_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'ticket_no']);
            $table->unique(['ticket_no']);
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'key']);
            $table->unique(['key']);
        });
        Schema::table('price_rules', function (Blueprint $table) {
            $table->dropUnique('price_rule_shop_unique');
            $table->unique(['make_id', 'car_model_id', 'repair_type_id'], 'price_rule_unique');
            $table->dropIndex('price_rules_make_id_idx');
        });

        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->dropColumn('shop_id');
            });
        }
    }
};
