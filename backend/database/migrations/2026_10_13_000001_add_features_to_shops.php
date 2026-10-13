<?php

use App\Models\Shop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // قائمة المميزات المفعّلة لهذا المحل (يتحكم بها super-admin فقط)
            $table->json('features')->nullable()->after('plan_id');
        });

        // المحلات الموجودة تحصل على كل المميزات (حتى لا تفقد وظائف كانت تعمل)
        $all = array_keys(Shop::AVAILABLE_FEATURES);
        DB::table('shops')->update(['features' => json_encode($all)]);
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('features');
        });
    }
};
