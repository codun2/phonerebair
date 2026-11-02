<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** السماح بعدّة أعطال لكل تذكرة — نحتفظ بـ repair_type_id كعطل رئيسي (أول عطل) للتوافق. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->json('repair_type_ids')->nullable()->after('repair_type_id');
        });

        // ترحيل: العطل المفرد الموجود يصير عنصراً وحيداً في المصفوفة
        DB::table('tickets')->whereNotNull('repair_type_id')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $r) {
                DB::table('tickets')->where('id', $r->id)
                    ->update(['repair_type_ids' => json_encode([(int) $r->repair_type_id])]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', fn (Blueprint $t) => $t->dropColumn('repair_type_ids'));
    }
};
