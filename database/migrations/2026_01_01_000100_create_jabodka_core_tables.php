<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('buyer_type')->nullable();
            $table->string('contact_number')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('landmark')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_name');
            $table->text('contact_details')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->enum('source_type', ['handmade', 'sourced', 'consignment']);
            $table->decimal('base_price', 10, 2);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size')->nullable();
            $table->string('version')->nullable();
            $table->integer('stock_count')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->timestamps();
        });

        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->enum('channel', ['walk_in', 'online']);
            $table->dateTime('transaction_date');
            $table->decimal('shipping_fee', 10, 2)->nullable();
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->enum('shipment_status', [
                'pending_payment',
                'ready_to_ship',
                'shipped',
                'delivered',
                'cancelled',
            ])->nullable();
            $table->timestamps();
        });

        Schema::create('sales_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('sales_transactions')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->integer('quantity');
            $table->decimal('price_at_sale', 10, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('sales_transactions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->enum('payment_method', ['cash', 'gcash']);
            $table->decimal('amount_paid', 10, 2);
            $table->enum('payment_status', ['pending', 'confirmed', 'refunded']);
            $table->dateTime('payment_date')->nullable();
            $table->string('reference_number')->nullable();
            $table->timestamps();
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_item_id')->constrained('sales_items');
            $table->foreignId('user_id')->constrained();
            $table->enum('return_type', ['refund', 'replacement']);
            $table->text('reason');
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->dateTime('return_date');
            $table->boolean('restocked')->default(false);
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('adjustment_type', ['restock', 'correction', 'damage', 'consignment_return']);
            $table->integer('quantity_changed');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sales_items');
        Schema::dropIfExists('sales_transactions');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
    }
};
