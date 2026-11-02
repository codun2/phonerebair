<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** رمز عام غير قابل للتخمين لكل تذكرة → رابط تتبّع مباشر للزبون بلا تسجيل دخول. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('public_token', 40)->nullable()->unique()->after('ticket_no');
        });

        // توليد رموز للتذاكر الموجودة
        DB::table('tickets')->whereNull('public_token')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $r) {
                DB::table('tickets')->where('id', $r->id)->update(['public_token' => Str::random(32)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
