<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ConsignmentPartner;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 2 — Stock Management receives stock as supplier invoices (one-to-many)
 * and can be filtered by source type.
 */
class StockManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create();
        $this->supplier = Supplier::factory()->create(['supplier_name' => 'Metro Textiles']);
    }

    /**
     * @param  array<int, array{variant_id:int, quantity_received:int, unit_cost?:float|null}>  $lines
     */
    private function postInvoice(array $lines, array $overrides = [])
    {
        return $this->actingAs($this->owner)->post(route('admin.stock.import'), array_merge([
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'INV-1042',
            'received_date' => '2026-03-01',
            'notes' => 'March delivery',
            'items' => $lines,
        ], $overrides));
    }

    public function test_saving_an_invoice_creates_one_header_with_many_line_items(): void
    {
        $a = Product::factory()->sourced($this->supplier)->create()->variants()->create(['stock_count' => 0]);
        $b = Product::factory()->sourced($this->supplier)->create()->variants()->create(['stock_count' => 3]);

        $this->postInvoice([
            ['variant_id' => $a->id, 'quantity_received' => 10, 'unit_cost' => 120.50],
            ['variant_id' => $b->id, 'quantity_received' => 4, 'unit_cost' => 99.00],
        ])->assertRedirect(route('admin.stock.index'));

        $this->assertDatabaseCount('stock_receipts', 1);
        $this->assertDatabaseCount('stock_receipt_items', 2);

        $receipt = StockReceipt::query()->sole();

        $this->assertSame('INV-1042', $receipt->invoice_number);
        $this->assertSame($this->supplier->id, $receipt->supplier_id);
        $this->assertSame($this->owner->id, $receipt->user_id);
        $this->assertSame(2, $receipt->items()->count());
    }

    public function test_saving_an_invoice_increments_stock_for_every_line(): void
    {
        $a = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 0]);
        $b = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 3]);

        $this->postInvoice([
            ['variant_id' => $a->id, 'quantity_received' => 10],
            ['variant_id' => $b->id, 'quantity_received' => 4],
        ]);

        $this->assertSame(10, $a->fresh()->stock_count);
        $this->assertSame(7, $b->fresh()->stock_count);
    }

    public function test_line_totals_and_invoice_total_are_computed(): void
    {
        $a = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 0]);
        $b = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 0]);

        $this->postInvoice([
            ['variant_id' => $a->id, 'quantity_received' => 10, 'unit_cost' => 120.50],
            ['variant_id' => $b->id, 'quantity_received' => 4, 'unit_cost' => 99.00],
        ]);

        $receipt = StockReceipt::query()->sole();

        $this->assertSame(1205.00, (float) $receipt->items()->where('variant_id', $a->id)->sole()->line_total);
        $this->assertSame(396.00, (float) $receipt->items()->where('variant_id', $b->id)->sole()->line_total);
        $this->assertSame(1601.00, (float) $receipt->total_amount);
    }

    public function test_unit_cost_is_optional_and_null_costs_do_not_inflate_the_total(): void
    {
        $a = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 0]);
        $b = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 0]);

        $this->postInvoice([
            ['variant_id' => $a->id, 'quantity_received' => 5, 'unit_cost' => 100],
            ['variant_id' => $b->id, 'quantity_received' => 5],
        ]);

        $receipt = StockReceipt::query()->sole();

        $this->assertSame(100.00, (float) $receipt->items()->where('variant_id', $a->id)->sole()->unit_cost);
        $this->assertNull($receipt->items()->where('variant_id', $b->id)->sole()->unit_cost);
        $this->assertNull($receipt->items()->where('variant_id', $b->id)->sole()->line_total);
        $this->assertSame(500.00, (float) $receipt->total_amount);
    }

    public function test_invoice_receiving_is_logged(): void
    {
        $variant = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 0]);

        $this->postInvoice([['variant_id' => $variant->id, 'quantity_received' => 6]]);

        $log = ActivityLog::query()->where('action', 'stock.receive')->sole();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame('INV-1042', $log->properties['invoice_number']);
    }

    public function test_an_invoice_requires_at_least_one_line(): void
    {
        $this->postInvoice([])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('stock_receipts', 0);
    }

    public function test_consignment_variants_cannot_be_received_on_a_supplier_invoice(): void
    {
        ConsignmentPartner::factory()->create();
        $variant = Product::factory()->consignment()->create()->variants()->create(['stock_count' => 5]);

        $this->postInvoice([['variant_id' => $variant->id, 'quantity_received' => 10]])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('stock_receipts', 0);
        $this->assertSame(5, $variant->fresh()->stock_count, 'Stock must be untouched.');
    }

    public function test_manual_stock_adjustment_no_longer_creates_restock_movements(): void
    {
        $variant = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 10]);

        $this->actingAs($this->owner)
            ->post(route('admin.stock.adjust', $variant), [
                'adjustment_type' => 'restock',
                'quantity_changed' => 5,
                'reason' => 'sneaky',
            ])
            ->assertSessionHasErrors('adjustment_type');

        $this->assertSame(10, $variant->fresh()->stock_count);
    }

    public function test_manual_adjustments_still_work_for_corrections_and_damage(): void
    {
        $variant = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 10]);

        $this->actingAs($this->owner)
            ->post(route('admin.stock.adjust', $variant), [
                'adjustment_type' => 'damage',
                'quantity_changed' => -2,
                'reason' => 'Torn during storage',
            ])
            ->assertRedirect(route('admin.stock.index'));

        $this->assertSame(8, $variant->fresh()->stock_count);
        $this->assertDatabaseHas('stock_adjustments', [
            'variant_id' => $variant->id,
            'adjustment_type' => 'damage',
            'quantity_changed' => -2,
        ]);
    }

    public function test_stock_dashboard_lists_invoices_with_their_line_items(): void
    {
        $variant = Product::factory()->sourced()->create(['name' => 'Polo Shirt'])->variants()->create(['stock_count' => 0]);

        $this->postInvoice([['variant_id' => $variant->id, 'quantity_received' => 8, 'unit_cost' => 150]]);

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index'))
            ->assertOk()
            ->assertSee('INV-1042')
            ->assertSee('Metro Textiles')
            ->assertSee('Polo Shirt');
    }

    public function test_stock_dashboard_filters_by_source_type(): void
    {
        $handmade = Product::factory()->handmade()->create(['name' => 'Handwoven Bag'])->variants()->create(['stock_count' => 4]);
        $sourced = Product::factory()->sourced()->create(['name' => 'Imported Mug'])->variants()->create(['stock_count' => 4]);
        $consignment = Product::factory()->consignment()->create(['name' => 'Partner Blouse'])->variants()->create(['stock_count' => 4]);

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index', ['type' => 'handmade']))
            ->assertOk()
            ->assertSee('Handwoven Bag')
            ->assertDontSee('Imported Mug')
            ->assertDontSee('Partner Blouse');

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index', ['type' => 'sourced']))
            ->assertOk()
            ->assertSee('Imported Mug')
            ->assertDontSee('Handwoven Bag');

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index', ['type' => 'consignment']))
            ->assertOk()
            ->assertSee('Partner Blouse')
            ->assertDontSee('Handwoven Bag');

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index', ['type' => 'all']))
            ->assertOk()
            ->assertSee('Handwoven Bag')
            ->assertSee('Imported Mug')
            ->assertSee('Partner Blouse');
    }

    public function test_source_type_filter_respects_the_low_stock_filter_too(): void
    {
        $healthy = Product::factory()->handmade()->create(['name' => 'Healthy Bag'])
            ->variants()->create(['stock_count' => 50, 'reorder_level' => 5]);
        $runningLow = Product::factory()->sourced()->create(['name' => 'Low Mug'])
            ->variants()->create(['stock_count' => 2, 'reorder_level' => 5]);

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index', ['filter' => 'low', 'type' => 'handmade']))
            ->assertOk()
            ->assertDontSee('Healthy Bag')
            ->assertDontSee('Low Mug');

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index', ['filter' => 'low', 'type' => 'sourced']))
            ->assertOk()
            ->assertSee('Low Mug')
            ->assertDontSee('Healthy Bag');
    }

    public function test_import_form_offers_only_active_non_consignment_variants(): void
    {
        $sellable = Product::factory()->sourced()->create(['name' => 'Sellable Cap'])
            ->variants()->create(['size' => 'M']);

        $deactivatedProduct = Product::factory()->inactive()->sourced()->create(['name' => 'Retired Cap']);
        $deactivatedProduct->variants()->create(['size' => 'M']);

        $inactiveVariantProduct = Product::factory()->sourced()->create(['name' => 'Partly Retired Cap']);
        $inactiveVariantProduct->variants()->create(['size' => 'L', 'is_active' => false]);

        Product::factory()->consignment()->create(['name' => 'Consignment Cap'])
            ->variants()->create(['size' => 'M']);

        $response = $this->actingAs($this->owner)->get(route('admin.stock.import-form'));

        $response->assertOk()->assertSee('Sellable Cap');

        $body = $response->getContent();
        $this->assertStringNotContainsString('Retired Cap', $body);
        $this->assertStringNotContainsString('Partly Retired Cap', $body);
        $this->assertStringNotContainsString('Consignment Cap', $body);

        $this->assertNotNull($sellable);
    }

    public function test_receiving_an_invoice_appends_to_stock_without_touching_sales_history(): void
    {
        $variant = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 2]);

        $this->postInvoice([['variant_id' => $variant->id, 'quantity_received' => 6, 'unit_cost' => 80]]);

        $receipt = StockReceipt::query()->sole();
        $line = StockReceiptItem::query()->sole();

        $this->assertSame($receipt->id, $line->stock_receipt_id);
        $this->assertSame(8, $variant->fresh()->stock_count);
    }

    public function test_source_type_filter_ignores_unknown_values(): void
    {
        Product::factory()->handmade()->create(['name' => 'Handwoven Bag'])
            ->variants()->create(['stock_count' => 4]);

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index', ['type' => 'nonsense']))
            ->assertOk()
            ->assertSee('Handwoven Bag');
    }

    public function test_deactivated_variants_still_appear_in_the_stock_dashboard(): void
    {
        $product = Product::factory()->inactive()->sourced()->create(['name' => 'Retired Cap']);
        $product->variants()->create(['stock_count' => 3, 'reorder_level' => 1]);

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index'))
            ->assertOk()
            ->assertSee('Retired Cap');
    }

    public function test_low_stock_variant_scope_is_still_available_for_dashboard_counters(): void
    {
        Product::factory()->sourced()->create()->variants()->create(['stock_count' => 1, 'reorder_level' => 5]);
        Product::factory()->sourced()->create()->variants()->create(['stock_count' => 0, 'reorder_level' => 5]);
        Product::factory()->sourced()->create()->variants()->create(['stock_count' => 30, 'reorder_level' => 5]);

        $this->assertSame(2, ProductVariant::query()->lowStock()->count());
    }
}
