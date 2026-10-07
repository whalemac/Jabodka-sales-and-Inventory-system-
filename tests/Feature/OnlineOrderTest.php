<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Module 4 — Payment status and shipment status are tracked separately and both
 * can be advanced inline from the orders table.
 */
class OnlineOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Product $product;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create();
        $this->product = Product::factory()->sourced()->create(['name' => 'Everyday Tee', 'base_price' => 250]);
        $this->variant = $this->product->variants()->create(['stock_count' => 50, 'reorder_level' => 2]);
    }

    /**
     * An online order with one item and an unpaid pending payment.
     */
    private function onlineOrder(array $attributes = []): SalesTransaction
    {
        $transaction = SalesTransaction::factory()->online()->create(array_merge([
            'user_id' => $this->owner->id,
        ], $attributes));

        $transaction->items()->create([
            'variant_id' => $this->variant->id,
            'quantity' => 1,
            'price_at_sale' => 250,
        ]);

        Payment::factory()->create([
            'transaction_id' => $transaction->id,
            'user_id' => $this->owner->id,
            'payment_method' => 'gcash',
            'amount_paid' => 250,
            'payment_status' => 'pending',
        ]);

        return $transaction->fresh();
    }

    public function test_new_online_orders_start_pending_shipment(): void
    {
        $this->actingAs($this->owner)
            ->post(route('admin.online.store'), [
                'items' => [['variant_id' => $this->variant->id, 'quantity' => 1, 'price' => 250]],
                'customer_name' => 'Ana Reyes',
                'contact_number' => '09171234567',
                'shipping_address' => '12 Kalaw Ave',
                'payment_method' => 'gcash',
                'amount_paid' => 250,
            ])
            ->assertRedirect(route('admin.online.index'));

        $order = SalesTransaction::query()->where('channel', 'online')->sole();

        $this->assertSame('pending', $order->shipment_status);
    }

    public function test_walk_in_orders_have_no_shipment_status(): void
    {
        $this->actingAs($this->owner)
            ->post(route('admin.walk-in.store'), [
                'items' => [['variant_id' => $this->variant->id, 'quantity' => 1, 'price' => 250]],
                'payment_method' => 'cash',
                'amount_paid' => 250,
            ]);

        $order = SalesTransaction::query()->where('channel', 'walk_in')->sole();

        $this->assertNull($order->shipment_status);
    }

    public function test_shipment_and_payment_status_move_independently(): void
    {
        $order = $this->onlineOrder();

        // Shipment advances while payment is still pending.
        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.shipment', $order), ['shipment_status' => 'preparing'])
            ->assertOk()
            ->assertJson(['shipment_status' => 'preparing']);

        $order = $order->fresh();
        $this->assertSame('preparing', $order->shipment_status);
        $this->assertSame('pending', $order->derivedPaymentStatus());

        // Payment confirms without touching shipment.
        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.payment-status', $order), ['payment_status' => 'confirmed'])
            ->assertOk()
            ->assertJson(['payment_status' => 'confirmed']);

        $order = $order->fresh();
        $this->assertSame('confirmed', $order->derivedPaymentStatus());
        $this->assertSame('preparing', $order->shipment_status);
    }

    public function test_shipment_status_can_progress_through_its_whole_lifecycle(): void
    {
        $order = $this->onlineOrder();

        foreach (['preparing', 'shipped', 'in_transit', 'delivered'] as $status) {
            $this->actingAs($this->owner)
                ->patchJson(route('admin.online.shipment', $order), ['shipment_status' => $status])
                ->assertOk();

            $this->assertSame($status, $order->fresh()->shipment_status);
        }

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.shipment', $order), ['shipment_status' => 'returned'])
            ->assertOk();

        $this->assertSame('returned', $order->fresh()->shipment_status);
    }

    public function test_invalid_shipment_statuses_are_rejected(): void
    {
        $order = $this->onlineOrder();

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.shipment', $order), ['shipment_status' => 'teleported'])
            ->assertStatus(422);

        $this->assertSame('pending', $order->fresh()->shipment_status);
    }

    public function test_invalid_payment_statuses_are_rejected(): void
    {
        $order = $this->onlineOrder();

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.payment-status', $order), ['payment_status' => 'maybe'])
            ->assertStatus(422);

        $this->assertSame('pending', $order->fresh()->derivedPaymentStatus());
    }

    public function test_quick_edit_saves_immediately_without_a_save_button(): void
    {
        $order = $this->onlineOrder();

        $response = $this->actingAs($this->owner)
            ->patch(route('admin.online.shipment', $order), ['shipment_status' => 'shipped']);

        $response->assertRedirect();
        $this->assertSame('shipped', $order->fresh()->shipment_status);
    }

    public function test_shipment_changes_are_logged_with_old_and_new_value(): void
    {
        $order = $this->onlineOrder(['shipment_status' => 'preparing']);

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.shipment', $order), ['shipment_status' => 'shipped']);

        $log = ActivityLog::query()->where('action', 'order.shipment.update')->sole();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame(SalesTransaction::class, $log->subject_type);
        $this->assertSame($order->id, $log->subject_id);
        $this->assertSame('preparing', $log->properties['from']);
        $this->assertSame('shipped', $log->properties['to']);
    }

    public function test_payment_status_changes_are_logged_with_old_and_new_value(): void
    {
        $order = $this->onlineOrder();

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.payment-status', $order), ['payment_status' => 'confirmed']);

        $log = ActivityLog::query()->where('action', 'order.payment.status')->sole();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame('pending', $log->properties['from']);
        $this->assertSame('confirmed', $log->properties['to']);
    }

    public function test_confirming_a_payment_quick_edit_confirms_the_pending_payment_row(): void
    {
        $order = $this->onlineOrder();

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.payment-status', $order), ['payment_status' => 'confirmed']);

        $this->assertDatabaseHas('payments', [
            'transaction_id' => $order->id,
            'payment_status' => 'confirmed',
        ]);
        $this->assertSame(0, Payment::query()->where('transaction_id', $order->id)->where('payment_status', 'pending')->count());
    }

    public function test_marking_a_payment_refunded_flips_the_confirmed_rows(): void
    {
        $order = $this->onlineOrder();

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.payment-status', $order), ['payment_status' => 'confirmed']);

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.payment-status', $order), ['payment_status' => 'refunded']);

        $this->assertSame('refunded', $order->fresh()->derivedPaymentStatus());
    }

    public function test_quick_edit_only_accepts_the_two_status_fields(): void
    {
        $order = $this->onlineOrder(['shipping_fee' => 75, 'courier' => 'J&T Express']);

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.shipment', $order), [
                'shipment_status' => 'shipped',
                'total' => 1,
                'customer_id' => 999,
            ])
            ->assertOk();

        $order = $order->fresh();

        $this->assertSame('shipped', $order->shipment_status);
        $this->assertEqualsWithDelta(75, (float) $order->shipping_fee, 0.001, 'Other fields must be untouched.');
    }

    public function test_tracking_number_and_courier_can_still_be_recorded_alongside_shipment(): void
    {
        $order = $this->onlineOrder();

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.shipment', $order), [
                'shipment_status' => 'shipped',
                'tracking_number' => 'JT123456789',
                'courier' => 'J&T Express',
            ])
            ->assertOk();

        $order = $order->fresh();

        $this->assertSame('JT123456789', $order->tracking_number);
        $this->assertSame('J&T Express', $order->courier);
    }

    public function test_status_endpoints_reject_walk_in_transactions(): void
    {
        $walkIn = SalesTransaction::factory()->walkIn()->create(['user_id' => $this->owner->id]);

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.shipment', $walkIn), ['shipment_status' => 'shipped'])
            ->assertNotFound();

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.payment-status', $walkIn), ['payment_status' => 'confirmed'])
            ->assertNotFound();
    }

    public function test_the_orders_table_shows_payment_and_shipment_as_two_separate_badges(): void
    {
        $this->onlineOrder(['shipment_status' => 'in_transit']);

        $body = $this->actingAs($this->owner)
            ->get(route('admin.online.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Payment Status', $body);
        $this->assertStringContainsString('Shipment Status', $body);
        $this->assertStringContainsString('In Transit', $body);
    }

    public function test_view_action_still_opens_the_full_order_detail(): void
    {
        $order = $this->onlineOrder();

        $this->actingAs($this->owner)
            ->get(route('admin.online.show', $order))
            ->assertOk()
            ->assertSee('Everyday Tee');

        $this->actingAs($this->owner)
            ->get(route('admin.online.index'))
            ->assertOk()
            ->assertSee(route('admin.online.show', $order), escape: false);
    }

    public function test_orders_can_be_filtered_by_shipment_status(): void
    {
        $this->onlineOrder(['shipment_status' => 'pending']);
        $this->onlineOrder(['shipment_status' => 'delivered']);

        $this->actingAs($this->owner)
            ->get(route('admin.online.index', ['status' => 'delivered']))
            ->assertOk();

        $this->assertSame(1, SalesTransaction::query()->where('shipment_status', 'delivered')->count());
    }

    public function test_status_lists_expose_every_shipment_and_payment_state(): void
    {
        $this->assertSame(
            ['pending', 'preparing', 'shipped', 'in_transit', 'delivered', 'returned'],
            array_keys(SalesTransaction::SHIPMENT_STATUSES)
        );

        $this->assertSame(
            ['pending', 'confirmed', 'refunded'],
            array_keys(SalesTransaction::PAYMENT_STATUSES)
        );
    }

    public function test_a_refund_through_returns_still_reports_the_right_payment_status(): void
    {
        $order = $this->onlineOrder();

        $this->actingAs($this->owner)
            ->patchJson(route('admin.online.payment-status', $order), ['payment_status' => 'confirmed']);

        $this->assertSame('confirmed', $order->fresh()->derivedPaymentStatus());
    }
}
