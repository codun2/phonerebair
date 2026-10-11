<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الدومين الأساسي لمحل صيانة الجوالات:
 * أنواع الأعطال، جدول الأسعار، الفنيون، التذاكر (+صور +سجل الحالات)، المخزون، الحجوزات، الإعدادات.
 */
return new class extends Migration
{
    public function up(): void
    {
        // أنواع الأعطال/الخدمات: شاشة، بطارية، شحن...
        Schema::create('repair_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->nullable();
            $table->string('icon', 60)->nullable();          // اسم أيقونة Tabler
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // جدول أسعار الأعطال — يغذّي حاسبة السعر الفوري بالموقع
        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('make_id')->constrained('makes')->cascadeOnDelete();
            $table->foreignId('car_model_id')->nullable()->constrained('car_models')->cascadeOnDelete();
            $table->foreignId('repair_type_id')->constrained('repair_types')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->unsignedSmallInteger('warranty_months')->default(3);
            $table->timestamps();

            $table->unique(['make_id', 'car_model_id', 'repair_type_id'], 'price_rule_unique');
        });

        // الفنيون
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 30)->nullable();
            $table->string('specialty', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // التذاكر — قلب النظام
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no', 20)->unique();          // RX-10248
            $table->string('customer_name', 120);
            $table->string('customer_phone', 30);
            $table->string('make', 60)->nullable();             // الماركة (نص)
            $table->string('model', 80)->nullable();            // الموديل (نص)
            $table->string('imei', 40)->nullable();
            $table->foreignId('repair_type_id')->nullable()->constrained('repair_types')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedSmallInteger('warranty_months')->nullable();
            $table->enum('status', [
                'received', 'awaiting_approval', 'in_repair', 'ready', 'delivered', 'cancelled',
            ])->default('received');
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('customer_phone');
        });

        // صور الجهاز قبل/بعد التصليح
        Schema::create('ticket_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('file_path');
            $table->enum('kind', ['before', 'after'])->default('before');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // سجل تغيّر حالات التذكرة (timeline)
        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('status', 40);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // المخزون — قطع الغيار والإكسسوارات
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('sku', 60)->nullable();
            $table->string('compatible', 120)->nullable();       // التوافق (iPhone 14...)
            $table->integer('qty')->default(0);
            $table->decimal('cost', 10, 2)->nullable();
            $table->unsignedSmallInteger('low_threshold')->default(3);
            $table->timestamps();
        });

        // الحجوزات/طلبات التسعير من الموقع العام
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('make', 60)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('repair_type', 80)->nullable();
            $table->enum('service_type', ['visit', 'pickup'])->default('visit');
            $table->string('preferred_time', 80)->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['new', 'contacted', 'converted', 'closed'])->default('new');
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });

        // إعدادات المحل (key/value)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('ticket_events');
        Schema::dropIfExists('ticket_media');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('technicians');
        Schema::dropIfExists('price_rules');
        Schema::dropIfExists('repair_types');
    }
};
