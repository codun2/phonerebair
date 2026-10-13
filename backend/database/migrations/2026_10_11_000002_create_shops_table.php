<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();          // phonerebair.smartunita.com/s/{slug}
            $table->string('owner_name', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('address', 200)->nullable();
            $table->string('logo_path')->nullable();
            // pending = بانتظار موافقة super-admin, active = فعّال, suspended = موقوف
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
