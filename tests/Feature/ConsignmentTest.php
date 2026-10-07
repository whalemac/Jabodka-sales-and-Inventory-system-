<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ConsignmentItem;
use App\Models\ConsignmentPartner;
use App\Models\ConsignmentPullout;
use App\Models\ConsignmentReceipt;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Module 3 — Consignment intake is batched into receipts and items can be pulled
 * out of the shop without erasing sales history.
 */
class ConsignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private ConsignmentPartner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create();
        $this->partner = ConsignmentPartner::factory()->create(['partner_name' => 'Tala Arts']);
    }

    private function consignmentVariant(int $stock = 0): ProductVariant
    {
        return Product::factory()->consignment()->create()->variants()->create(['stock_count' => $stock]);
    }

    private function postDropOff(array $lines, array $overrides = [])
    {
        return $this->actingAs($this->owner)->post(route('admin.consignment.items.store'), array_merge([
            'partner_id' => $this->partner->id,
            'received_date' => '2026-03-05',
            'notes' => 'Friday visit',
            'items' => $lines,
        ], $overrides));
    }

    // ─── Batch receiving ─────────────────────────────────────────────────────

    public function test_a_drop_off_creates_one_receipt_with_many_items(): void
    {
        $a = $this->consignmentVariant();
        $b = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $a->id, 'units_delivered' => 6, 'agreed_base_price' => 200, 'shop_markup' => 150],
            ['variant_id' => $b->id, 'units_delivered' => 4, 'agreed_base_price' => 180, 'shop_markup' => 120],
        ])->assertRedirect(route('admin.consignment.index'));

        $this->assertDatabaseCount('consignment_receipts', 1);
        $this->assertDatabaseCount('consignment_items', 2);

        $receipt = ConsignmentReceipt::query()->sole();

        $this->assertSame($this->partner->id, $receipt->partner_id);
        $this->assertSame($this->owner->id, $receipt->user_id);
        $this->assertSame(2, $receipt->items()->count());

        $this->assertSame(6, $a->fresh()->stock_count);
        $this->assertSame(4, $b->fresh()->stock_count);
    }

    public function test_every_item_links_back_to_its_batch(): void
    {
        $a = $this->consignmentVariant();
        $b = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $a->id, 'units_delivered' => 6, 'agreed_base_price' => 200, 'shop_markup' => 150],
            ['variant_id' => $b->id, 'units_delivered' => 4, 'agreed_base_price' => 180, 'shop_markup' => 120],
        ]);

        $receipt = ConsignmentReceipt::query()->sole();

        $this->assertEqualsCanonicalizing(
            [$a->id, $b->id],
            $receipt->items()->pluck('variant_id')->all()
        );

        foreach ($receipt->items as $item) {
            $this->assertSame($receipt->id, $item->consignment_receipt_id);
            $this->assertSame($this->partner->id, $item->partner_id);
        }
    }

    public function test_the_consignment_receipt_link_is_nullable_for_legacy_items(): void
    {
        $variant = $this->consignmentVariant();

        $legacy = ConsignmentItem::query()->create([
            'partner_id' => $this->partner->id,
            'variant_id' => $variant->id,
            'units_delivered' => 3,
            'agreed_base_price' => 100,
            'shop_markup' => 50,
            'received_at' => now(),
        ]);

        $this->assertNull($legacy->fresh()->consignment_receipt_id);
    }

    public function test_non_consignment_variants_cannot_be_received_on_a_drop_off(): void
    {
        $variant = Product::factory()->sourced()->create()->variants()->create(['stock_count' => 5]);

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 6, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('consignment_receipts', 0);
        $this->assertSame(5, $variant->fresh()->stock_count);
    }

    public function test_a_drop_off_requires_at_least_one_line(): void
    {
        $this->postDropOff([])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('consignment_receipts', 0);
    }

    public function test_receiving_a_drop_off_is_logged(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 6, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $log = ActivityLog::query()->where('action', 'consignment.receipt.create')->sole();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame(1, $log->properties['line_count']);
    }

    public function test_the_intake_form_only_offers_active_consignment_variants(): void
    {
        $sellable = Product::factory()->consignment()->create(['name' => 'Partner Blouse'])
            ->variants()->create(['size' => 'M']);

        Product::factory()->inactive()->consignment()->create(['name' => 'Retired Blouse'])
            ->variants()->create(['size' => 'M']);

        Product::factory()->sourced()->create(['name' => 'Own Mug'])
            ->variants()->create(['size' => 'M']);

        $body = $this->actingAs($this->owner)
            ->get(route('admin.consignment.items.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Partner Blouse', $body);
        $this->assertStringNotContainsString('Retired Blouse', $body);
        $this->assertStringNotContainsString('Own Mug', $body);
        $this->assertNotNull($sellable);
    }

    // ─── Pull-out ────────────────────────────────────────────────────────────

    public function test_pulling_out_creates_a_pullout_row_and_decrements_stock(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 10, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();
        $this->assertSame(10, $variant->fresh()->stock_count);

        $this->actingAs($this->owner)
            ->from(route('admin.consignment.index'))
            ->post(route('admin.consignment.items.pullout', $item), [
                'quantity_pulled' => 4,
                'pullout_date' => '2026-03-10',
                'reason' => 'Partner asked for them back',
            ])
            ->assertRedirect(route('admin.consignment.index'));

        $this->assertDatabaseCount('consignment_pullouts', 1);

        $pullout = ConsignmentPullout::query()->sole();

        $this->assertSame($item->id, $pullout->consignment_item_id);
        $this->assertSame($this->owner->id, $pullout->user_id);
        $this->assertSame(4, $pullout->quantity_pulled);
        $this->assertSame('2026-03-10', $pullout->pullout_date->format('Y-m-d'));
        $this->assertSame('Partner asked for them back', $pullout->reason);

        $this->assertSame(6, $variant->fresh()->stock_count);
    }

    public function test_pulling_out_also_leaves_a_consignment_return_stock_trail(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 10, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();

        $this->actingAs($this->owner)->post(route('admin.consignment.items.pullout', $item), [
            'quantity_pulled' => 3,
            'pullout_date' => '2026-03-10',
            'reason' => 'Season ended',
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'variant_id' => $variant->id,
            'adjustment_type' => 'consignment_return',
            'quantity_changed' => -3,
        ]);
    }

    public function test_pulling_out_is_logged(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 10, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();

        $this->actingAs($this->owner)->post(route('admin.consignment.items.pullout', $item), [
            'quantity_pulled' => 2,
            'pullout_date' => '2026-03-10',
        ]);

        $this->assertSame(1, ActivityLog::query()->where('action', 'consignment.pullout')->count());
    }

    public function test_remaining_quantity_is_delivered_minus_sold_minus_pulled(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 10, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();

        $this->assertSame(10, $item->remainingQuantity());

        // Sell 3 units.
        $transaction = SalesTransaction::factory()->walkIn()->create(['user_id' => $this->owner->id]);
        SalesItem::query()->create([
            'transaction_id' => $transaction->id,
            'variant_id' => $variant->id,
            'quantity' => 3,
            'price_at_sale' => 350,
        ]);

        $item = ConsignmentItem::query()->with('pullouts')->sole();
        ConsignmentItem::allocateSoldQuantities(collect([$item]));

        $this->assertSame(3, $item->unitsSoldAllocated());
        $this->assertSame(7, $item->remainingQuantity());

        // Pull 5 out.
        $this->actingAs($this->owner)->post(route('admin.consignment.items.pullout', $item), [
            'quantity_pulled' => 5,
            'pullout_date' => '2026-03-10',
        ]);

        $item = ConsignmentItem::query()->with('pullouts')->sole();
        ConsignmentItem::allocateSoldQuantities(collect([$item]));

        $this->assertSame(10, $item->units_delivered);
        $this->assertSame(3, $item->unitsSoldAllocated());
        $this->assertSame(5, $item->unitsPulled());
        $this->assertSame(2, $item->remainingQuantity());
    }

    public function test_sold_units_are_allocated_first_in_fifo_order_across_batches(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 4, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ], ['received_date' => '2026-01-05']);

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 6, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ], ['received_date' => '2026-02-05']);

        $transaction = SalesTransaction::factory()->walkIn()->create(['user_id' => $this->owner->id]);
        SalesItem::query()->create([
            'transaction_id' => $transaction->id,
            'variant_id' => $variant->id,
            'quantity' => 6,
            'price_at_sale' => 350,
        ]);

        $items = ConsignmentItem::query()->orderBy('received_at')->get();
        ConsignmentItem::allocateSoldQuantities($items);

        $this->assertSame(4, $items[0]->unitsSoldAllocated(), 'First batch absorbs the first sales.');
        $this->assertSame(2, $items[1]->unitsSoldAllocated());
        $this->assertSame(0, $items[0]->remainingQuantity());
        $this->assertSame(4, $items[1]->remainingQuantity());
    }

    public function test_cannot_pull_out_more_than_remains(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 5, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();

        $this->actingAs($this->owner)
            ->from(route('admin.consignment.items.pullout-form', $item))
            ->post(route('admin.consignment.items.pullout', $item), [
                'quantity_pulled' => 9,
                'pullout_date' => '2026-03-10',
            ])
            ->assertSessionHasErrors('quantity_pulled');

        $this->assertDatabaseCount('consignment_pullouts', 0);
        $this->assertSame(5, $variant->fresh()->stock_count);
    }

    public function test_a_fully_pulled_out_item_is_marked_but_keeps_its_sales_history(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 4, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();

        $transaction = SalesTransaction::factory()->walkIn()->create(['user_id' => $this->owner->id]);
        $saleItem = SalesItem::query()->create([
            'transaction_id' => $transaction->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'price_at_sale' => 350,
        ]);

        $this->actingAs($this->owner)->post(route('admin.consignment.items.pullout', $item), [
            'quantity_pulled' => 3,
            'pullout_date' => '2026-03-10',
            'reason' => 'Arrangement ended',
        ]);

        $item = ConsignmentItem::query()->with('pullouts')->sole();
        ConsignmentItem::allocateSoldQuantities(collect([$item]));

        $this->assertSame(4, $item->units_delivered);
        $this->assertSame(1, $item->unitsSoldAllocated());
        $this->assertSame(3, $item->unitsPulled());
        $this->assertSame(0, $item->remainingQuantity());
        $this->assertTrue($item->isFullyPulledOut());

        // Still listed, badged, and the sale is untouched.
        $this->actingAs($this->owner)
            ->get(route('admin.consignment.index'))
            ->assertOk()
            ->assertSee('Pulled Out');

        $this->assertDatabaseHas('sales_items', ['id' => $saleItem->id]);
        $this->assertDatabaseHas('consignment_items', ['id' => $item->id]);
    }

    public function test_pullout_form_shows_delivered_sold_and_remaining(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 9, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();

        $this->actingAs($this->owner)
            ->get(route('admin.consignment.items.pullout-form', $item))
            ->assertOk()
            ->assertSee('Delivered')
            ->assertSee('On floor');
    }

    public function test_pullout_records_a_default_reason_when_none_is_given(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 5, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();

        $this->actingAs($this->owner)->post(route('admin.consignment.items.pullout', $item), [
            'quantity_pulled' => 1,
            'pullout_date' => '2026-03-10',
        ]);

        $pullout = ConsignmentPullout::query()->sole();

        $this->assertSame($item->id, $pullout->consignment_item_id);
        $this->assertNull($pullout->reason);

        $this->assertDatabaseHas('stock_adjustments', [
            'variant_id' => $variant->id,
            'adjustment_type' => 'consignment_return',
            'reason' => 'Consignment pull-out',
        ]);
    }

    public function test_multiple_pullouts_accumulate_against_one_item(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 10, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();

        foreach ([3, 2] as $qty) {
            $this->actingAs($this->owner)->post(route('admin.consignment.items.pullout', $item), [
                'quantity_pulled' => $qty,
                'pullout_date' => '2026-03-10',
            ]);
        }

        $item = ConsignmentItem::query()->with('pullouts')->sole();

        $this->assertSame(2, $item->pullouts()->count());
        $this->assertSame(5, $item->unitsPulled());
        $this->assertSame(5, $item->remainingQuantity());
        $this->assertSame(5, $variant->fresh()->stock_count);
    }

    public function test_consignment_receipt_items_survive_alongside_legacy_items(): void
    {
        $legacyVariant = $this->consignmentVariant();
        $newVariant = $this->consignmentVariant();

        ConsignmentItem::query()->create([
            'partner_id' => $this->partner->id,
            'variant_id' => $legacyVariant->id,
            'units_delivered' => 2,
            'agreed_base_price' => 100,
            'shop_markup' => 50,
            'received_at' => now(),
        ]);

        $this->postDropOff([
            ['variant_id' => $newVariant->id, 'units_delivered' => 7, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $this->assertSame(2, ConsignmentItem::query()->count());
        $this->assertSame(1, ConsignmentItem::query()->whereNotNull('consignment_receipt_id')->count());
        $this->assertSame(1, ConsignmentItem::query()->whereNull('consignment_receipt_id')->count());

        $this->actingAs($this->owner)->get(route('admin.consignment.index'))->assertOk();
    }

    public function test_pullouts_table_is_never_deleted_when_an_item_is_fully_pulled(): void
    {
        $variant = $this->consignmentVariant();

        $this->postDropOff([
            ['variant_id' => $variant->id, 'units_delivered' => 2, 'agreed_base_price' => 200, 'shop_markup' => 150],
        ]);

        $item = ConsignmentItem::query()->sole();

        $this->actingAs($this->owner)->post(route('admin.consignment.items.pullout', $item), [
            'quantity_pulled' => 2,
            'pullout_date' => '2026-03-10',
        ]);

        $this->assertSame(1, ConsignmentPullout::query()->count());
        $this->assertSame(1, ConsignmentItem::query()->count());
        $this->assertSame(0, $variant->fresh()->stock_count);
        $this->assertSame(1, DB::table('consignment_pullouts')->count());
    }
}
