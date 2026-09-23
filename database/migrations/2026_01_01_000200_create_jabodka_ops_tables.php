<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_type');
            $table->enum('status', ['active', 'expired', 'cancelled']);
            $table->boolean('terms_accepted')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('consignment_partners', function (Blueprint $table) {
            $table->id();
            $table->string('partner_name');
            $table->text('contact_details')->nullable();
            $table->timestamps();
        });

        Schema::create('consignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('consignment_partners')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->integer('units_delivered');
            $table->decimal('agreed_base_price', 10, 2);
            $table->decimal('shop_markup', 10, 2);
            $table->dateTime('received_at');
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->unique()->constrained('sales_transactions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('receipt_number')->unique();
            $table->dateTime('issued_at');
            $table->integer('reprint_count')->default(0);
            $table->dateTime('last_printed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('consignment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('consignment_partners');
            $table->foreignId('user_id')->constrained();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_status', ['pending', 'paid']);
            $table->string('reference_number')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('consignment_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_payment_id')->constrained('consignment_payments')->cascadeOnDelete();
            $table->foreignId('sales_item_id')->constrained('sales_items');
            $table->foreignId('consignment_item_id')->constrained('consignment_items');
            $table->integer('quantity');
            $table->decimal('partner_amount', 10, 2);
            $table->timestamps();
        });

        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_name');
            $table->string('unit');
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->foreignId('user_id')->constrained();
            $table->integer('quantity_produced');
            $table->date('production_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('material_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('raw_materials');
            $table->foreignId('production_id')->nullable()->constrained('production_logs')->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->enum('transaction_type', ['received', 'used']);
            $table->decimal('quantity', 10, 2);
            $table->dateTime('logged_at');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('material_transactions');
        Schema::dropIfExists('production_logs');
        Schema::dropIfExists('raw_materials');
        Schema::dropIfExists('consignment_payment_items');
        Schema::dropIfExists('consignment_payments');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('consignment_items');
        Schema::dropIfExists('consignment_partners');
        Schema::dropIfExists('subscriptions');
    }
};
