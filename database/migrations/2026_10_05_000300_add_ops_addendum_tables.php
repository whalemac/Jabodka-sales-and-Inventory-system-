<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shipment lifecycle, independent of payment status.
     *
     * @var array<int, string>
     */
    private const SHIPMENT_STATUSES = [
        'pending',
        'preparing',
        'shipped',
        'in_transit',
        'delivered',
        'returned',
    ];

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('invoice_number');
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_receipt_id')->constrained('stock_receipts')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->integer('quantity_received');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('line_total', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('consignment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('consignment_partners');
            $table->foreignId('user_id')->constrained();
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('consignment_items', function (Blueprint $table) {
            $table->foreignId('consignment_receipt_id')
                ->nullable()
                ->constrained('consignment_receipts')
                ->nullOnDelete();
        });

        Schema::create('consignment_pullouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_item_id')->constrained('consignment_items');
            $table->foreignId('user_id')->constrained();
            $table->integer('quantity_pulled');
            $table->date('pullout_date');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        $this->migrateShipmentStatus();
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_pullouts');

        Schema::table('consignment_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consignment_receipt_id');
        });

        Schema::dropIfExists('consignment_receipts');
        Schema::dropIfExists('stock_receipt_items');
        Schema::dropIfExists('stock_receipts');

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    /**
     * Shipment statuses used to be conflated with payment status. Rebuild the
     * column against the new scale, remapping any legacy values first. The
     * column is dropped and re-added rather than altered so this behaves the
     * same on MySQL and SQLite (which compiles enum to a CHECK constraint).
     */
    private function migrateShipmentStatus(): void
    {
        $map = [
            'pending_payment' => 'pending',
            'ready_to_ship' => 'preparing',
            'cancelled' => 'returned',
        ];

        $existing = DB::table('sales_transactions')
            ->select('id', 'shipment_status')
            ->whereNotNull('shipment_status')
            ->get();

        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn('shipment_status');
        });

        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->enum('shipment_status', self::SHIPMENT_STATUSES)
                ->nullable()
                ->default('pending');
        });

        foreach ($existing as $row) {
            DB::table('sales_transactions')
                ->where('id', $row->id)
                ->update(['shipment_status' => $map[$row->shipment_status] ?? $row->shipment_status]);
        }

        // Shipment only applies to online orders; re-adding the column backfills
        // walk-in rows with the default, so clear them back out.
        DB::table('sales_transactions')
            ->where('channel', 'walk_in')
            ->update(['shipment_status' => null]);
    }
};
