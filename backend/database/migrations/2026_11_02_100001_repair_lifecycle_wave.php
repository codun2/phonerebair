<?php

use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * موجة «دورة حياة الصيانة الكاملة»:
 * عملاء + توسعة التذاكر (حالة الجهاز/الملحقات/كلمة المرور/الموافقة/موعد التسليم)
 * + قطع مستخدمة بالتذكرة + حركات مخزون + مدفوعات + مصروفات.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- العملاء ----
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('alt_phone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('address', 200)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'phone']);
        });

        // ---- توسعة التذاكر ----
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('ticket_no')
                ->constrained('customers')->nullOnDelete();
            $table->string('device_type', 30)->default('phone')->after('customer_phone'); // phone/tablet/laptop/watch/other
            $table->string('serial', 60)->nullable()->after('imei');
            $table->json('condition')->nullable()->after('description');     // حالة الجهاز عند الاستلام
            $table->json('accessories')->nullable()->after('condition');     // الملحقات المستلمة
            $table->text('device_password')->nullable()->after('accessories'); // مشفّرة (encrypted cast)
            $table->decimal('estimated_cost', 10, 2)->nullable()->after('device_password');
            $table->date('promised_at')->nullable()->after('price');          // موعد التسليم المتوقع
            $table->timestamp('approved_at')->nullable()->after('promised_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->string('approval_via', 30)->nullable()->after('approved_by'); // in_person/phone/whatsapp
        });

        // الحالة من enum إلى نص حتى نضيف مراحل جديدة بحرّية
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('status', 30)->default('received')->change();
        });

        // ---- القطع المستخدمة في التذكرة (snapshot للتكلفة والسعر وقت الاستخدام) ----
        Schema::create('ticket_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('qty')->default(1);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ---- المخزون: سعر بيع + مورّد ----
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('sale_price', 10, 2)->nullable()->after('cost');
            $table->string('supplier', 120)->nullable()->after('sale_price');
        });

        // ---- سجل حركات المخزون ----
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type', 20); // purchase/use/return/damage/adjust
            $table->integer('qty');     // موجب يزيد، سالب ينقص
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('note', 300)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['inventory_item_id', 'created_at']);
        });

        // ---- المدفوعات (دفعات جزئية على التذاكر) ----
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 20)->default('cash'); // cash/card/transfer/other
            $table->string('note', 300)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at');
            $table->timestamps();
            $table->index(['shop_id', 'paid_at']);
        });

        // ---- المصروفات ----
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('category', 60)->nullable(); // إيجار/كهرباء/رواتب/مشتريات/أخرى
            $table->decimal('amount', 10, 2);
            $table->date('spent_at');
            $table->string('note', 300)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['shop_id', 'spent_at']);
        });

        // ---- ترحيل العملاء من التذاكر والحجوزات الموجودة ----
        $pairs = DB::table('tickets')
            ->select('shop_id', 'customer_phone as phone', DB::raw('MAX(customer_name) as name'), DB::raw('MIN(created_at) as created_at'))
            ->groupBy('shop_id', 'customer_phone')->get();
        foreach ($pairs as $p) {
            $id = DB::table('customers')->insertGetId([
                'shop_id' => $p->shop_id, 'name' => $p->name, 'phone' => $p->phone,
                'created_at' => $p->created_at, 'updated_at' => now(),
            ]);
            DB::table('tickets')->where('shop_id', $p->shop_id)
                ->where('customer_phone', $p->phone)->update(['customer_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('stock_movements');
        Schema::table('inventory_items', fn (Blueprint $t) => $t->dropColumn(['sale_price', 'supplier']));
        Schema::dropIfExists('ticket_parts');
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['device_type', 'serial', 'condition', 'accessories', 'device_password',
                'estimated_cost', 'promised_at', 'approved_at', 'approval_via']);
        });
        Schema::dropIfExists('customers');
    }
};
