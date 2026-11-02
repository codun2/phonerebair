<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** الموردون: ملف لكل مورّد قطع غيار + ربط قطع المخزون به. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('contact_name', 120)->nullable();   // الشخص المسؤول
            $table->string('phone', 30)->nullable();
            $table->string('alt_phone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('website', 160)->nullable();
            $table->string('address', 200)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('tax_number', 30)->nullable();
            $table->string('payment_terms', 120)->nullable(); // شروط الدفع (نقدي/آجل 30 يوم...)
            $table->string('bank_details', 300)->nullable();
            $table->string('categories', 200)->nullable();     // التخصص: شاشات، بطاريات...
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['shop_id', 'name']);
        });

        // ربط القطعة بالمورد (نحتفظ بالنص القديم supplier كمرجع)
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('supplier')->constrained('suppliers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', fn (Blueprint $t) => $t->dropConstrainedForeignId('supplier_id'));
        Schema::dropIfExists('suppliers');
    }
};
