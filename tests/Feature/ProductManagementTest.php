<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductController;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Module 1 — Product Management deactivates instead of deletes.
 */
class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create();
    }

    public function test_there_is_no_product_destroy_route_or_method(): void
    {
        $this->assertNull(
            Route::getRoutes()->getByName('admin.products.destroy'),
            'The products destroy route must not be registered.'
        );

        $this->assertFalse(
            method_exists(ProductController::class, 'destroy'),
            'ProductController must not expose a destroy() method.'
        );
    }

    public function test_products_default_to_active(): void
    {
        $product = Product::factory()->create();
        $variant = $product->variants()->create(['size' => 'M']);

        $this->assertTrue($product->fresh()->is_active);
        $this->assertTrue($variant->fresh()->is_active);
    }

    public function test_owner_can_deactivate_and_reactivate_a_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->owner)
            ->from(route('admin.products.index'))
            ->patch(route('admin.products.toggle-active', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertFalse($product->fresh()->is_active);

        $this->actingAs($this->owner)
            ->patch(route('admin.products.toggle-active', $product));

        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_deactivation_is_logged_with_old_and_new_value(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->owner)
            ->patch(route('admin.products.toggle-active', $product));

        $log = ActivityLog::query()
            ->where('action', 'product.deactivate')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertTrue($log->properties['from']);
        $this->assertFalse($log->properties['to']);
    }

    public function test_a_variant_can_be_deactivated_independently_of_its_product(): void
    {
        $product = Product::factory()->create();
        $keep = $product->variants()->create(['size' => 'M', 'stock_count' => 10, 'reorder_level' => 2]);
        $drop = $product->variants()->create(['size' => 'XL', 'stock_count' => 10, 'reorder_level' => 2]);

        $this->actingAs($this->owner)
            ->patch(route('admin.products.variants.toggle-active', $drop));

        $this->assertFalse($drop->fresh()->is_active);
        $this->assertTrue($keep->fresh()->is_active, 'Sibling variants must stay active.');
        $this->assertTrue($product->fresh()->is_active, 'The parent product must stay active.');
    }

    public function test_deactivated_products_disappear_from_order_search(): void
    {
        $active = Product::factory()->create(['name' => 'Active Tee']);
        $active->variants()->create(['size' => 'M', 'stock_count' => 5, 'reorder_level' => 1]);

        $inactive = Product::factory()->inactive()->create(['name' => 'Retired Tee']);
        $inactive->variants()->create(['size' => 'M', 'stock_count' => 5, 'reorder_level' => 1]);

        $response = $this->actingAs($this->owner)
            ->getJson(route('admin.walk-in.search', ['q' => 'Tee']));

        $response->assertOk();

        $names = collect($response->json())->pluck('name');

        $this->assertTrue($names->contains(fn ($n) => str_contains($n, 'Active Tee')));
        $this->assertFalse($names->contains(fn ($n) => str_contains($n, 'Retired Tee')));
    }

    public function test_a_deactivated_variant_disappears_while_its_product_stays_active(): void
    {
        $product = Product::factory()->create(['name' => 'Logo Shirt']);
        $product->variants()->create(['size' => 'M', 'stock_count' => 5, 'reorder_level' => 1]);
        $product->variants()->create(['size' => 'XL', 'stock_count' => 5, 'reorder_level' => 1])->update(['is_active' => false]);

        $names = collect(
            $this->actingAs($this->owner)
                ->getJson(route('admin.walk-in.search', ['q' => 'Logo Shirt']))
                ->json()
        )->pluck('name');

        $this->assertTrue($names->contains(fn ($n) => str_contains($n, 'M')));
        $this->assertFalse($names->contains(fn ($n) => str_contains($n, 'XL')));
    }

    public function test_a_deactivated_product_cannot_be_sold_even_if_posted_directly(): void
    {
        $product = Product::factory()->inactive()->create();
        $variant = $product->variants()->create(['stock_count' => 10, 'reorder_level' => 1]);

        $this->actingAs($this->owner)
            ->post(route('admin.walk-in.store'), [
                'items' => [['variant_id' => $variant->id, 'quantity' => 1, 'price' => 100]],
                'payment_method' => 'cash',
                'amount_paid' => 100,
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales_transactions', 0);
        $this->assertSame(10, $variant->fresh()->stock_count);
    }

    public function test_deactivated_products_still_appear_in_product_management(): void
    {
        Product::factory()->inactive()->create(['name' => 'Retired Hoodie']);

        $this->actingAs($this->owner)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Retired Hoodie')
            ->assertSee('Inactive');
    }

    public function test_product_management_can_filter_by_active_status(): void
    {
        Product::factory()->create(['name' => 'Live Item']);
        Product::factory()->inactive()->create(['name' => 'Dead Item']);

        $this->actingAs($this->owner)
            ->get(route('admin.products.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Live Item')
            ->assertDontSee('Dead Item');

        $this->actingAs($this->owner)
            ->get(route('admin.products.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Dead Item')
            ->assertDontSee('Live Item');
    }

    public function test_deactivating_keeps_all_sales_history_intact_and_viewable(): void
    {
        $product = Product::factory()->create(['name' => 'Classic Tee']);
        $variant = $product->variants()->create(['stock_count' => 10, 'reorder_level' => 1]);

        $transaction = SalesTransaction::factory()->walkIn()->create(['user_id' => $this->owner->id]);
        $saleItem = SalesItem::query()->create([
            'transaction_id' => $transaction->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'price_at_sale' => 250,
        ]);

        $this->actingAs($this->owner)->patch(route('admin.products.toggle-active', $product));

        $this->assertDatabaseHas('sales_items', ['id' => $saleItem->id, 'variant_id' => $variant->id]);
        $this->assertDatabaseCount('sales_items', 1);

        // The historical sale is still reachable from reports.
        $this->actingAs($this->owner)
            ->get(route('admin.reports.sales', ['range' => 'all']))
            ->assertOk()
            ->assertSee('Classic Tee');
    }

    public function test_saved_variant_is_active_state_persists_on_update(): void
    {
        $product = Product::factory()->create();
        $variant = $product->variants()->create([
            'size' => 'M', 'stock_count' => 7, 'reorder_level' => 2, 'is_active' => false,
        ]);

        $this->actingAs($this->owner)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'source_type' => $product->source_type,
                'base_price' => $product->base_price,
                'variants' => [
                    ['id' => $variant->id, 'size' => 'M', 'stock_count' => 7, 'reorder_level' => 2, 'is_active' => 0],
                ],
            ])
            ->assertRedirect(route('admin.products.edit', $product));

        $this->assertFalse($variant->fresh()->is_active);
    }

    public function test_sellable_scope_requires_both_product_and_variant_active(): void
    {
        $product = Product::factory()->create();
        $ok = $product->variants()->create(['is_active' => true, 'size' => 'M']);
        $variantOff = $product->variants()->create(['is_active' => false, 'size' => 'L']);

        $productOff = Product::factory()->inactive()->create();
        $productOkVariant = $productOff->variants()->create(['is_active' => true, 'size' => 'S']);

        $sellable = ProductVariant::query()->sellable()->pluck('id');

        $this->assertTrue($sellable->contains($ok->id));
        $this->assertFalse($sellable->contains($variantOff->id));
        $this->assertFalse($sellable->contains($productOkVariant->id));
    }
}
