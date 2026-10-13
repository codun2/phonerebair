<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // super_admin => null. مالك/موظف المحل => shop_id الخاص بمحله
            $table->foreignId('shop_id')->nullable()->after('id')
                ->constrained('shops')->nullOnDelete();
        });
        // الأدوار صارت: super_admin | admin (مالك المحل) | staff
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });
    }
};
