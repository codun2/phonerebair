<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** حقول إضافية لملف العميل: نوع العميل، المدينة، الشركة، الرقم الضريبي. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_type', 20)->default('individual')->after('name'); // individual/business
            $table->string('city', 80)->nullable()->after('address');
            $table->string('company', 120)->nullable()->after('city');
            $table->string('tax_number', 30)->nullable()->after('company');
        });
    }

    public function down(): void
    {
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn(['customer_type', 'city', 'company', 'tax_number']));
    }
};
