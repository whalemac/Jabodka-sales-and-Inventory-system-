<?php

namespace Tests\Feature;

use App\Models\ConsignmentItem;
use App\Models\ConsignmentPartner;
use App\Models\ConsignmentReceipt;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\StockReceipt;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OnlineOrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every owner-facing page must render with a realistic amount of data present.
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->admin()->create(['username' => 'admin']);
        $this->seed(DatabaseSeeder::class);
        $this->seed(OnlineOrderSeeder::class);
    }

    public static function adminPages(): array
    {
        $pages = [
            'dashboard' => '/dashboard',
            'products' => '/admin/products',
            'products create' => '/admin/products/create',
            'stock' => '/admin/stock',
            'stock import' => '/admin/stock/import',
            'suppliers' => '/admin/suppliers',
            'consignment' => '/admin/consignment',
            'consignment new' => '/admin/consignment/items/create',
            'consignment pay' => '/admin/consignment/payouts',
            'online orders' => '/admin/online',
            'online create' => '/admin/online/create',
            'walk-in new' => '/admin/walk-in/create',
            'walk-in history' => '/admin/walk-in',
            'returns' => '/admin/returns',
            'reports' => '/admin/reports',
            'reports sales' => '/admin/reports/sales',
            'reports stock' => '/admin/reports/stock-movement',
            'reports best' => '/admin/reports/best-sellers',
            'reports consign' => '/admin/reports/consignment',
        ];

        $sets = [];
        foreach ($pages as $name => $path) {
            $sets[$name] = [$path];
        }

        return $sets;
    }

    #[DataProvider('adminPages')]
    public function test_admin_page_renders(string $path): void
    {
        $this->actingAs($this->owner)->get($path)->assertOk();
    }

    public function test_product_edit_page_renders_with_variants(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->owner)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_stock_adjust_page_renders(): void
    {
        $variant = Product::factory()->create()->variants()->create();

        $this->actingAs($this->owner)
            ->get(route('admin.stock.adjust-form', $variant))
            ->assertOk();
    }

    public function test_online_order_detail_page_renders(): void
    {
        $order = SalesTransaction::query()->where('channel', 'online')->firstOrFail();

        $this->actingAs($this->owner)
            ->get(route('admin.online.show', $order))
            ->assertOk();
    }

    public function test_supplier_invoice_expands_with_its_line_items(): void
    {
        $variant = Product::factory()->sourced()->create(['name' => 'Invoice Line Item'])->variants()->create();

        $receipt = StockReceipt::query()->create([
            'supplier_id' => Supplier::factory()->create()->id,
            'user_id' => $this->owner->id,
            'invoice_number' => 'INV-9001',
            'received_date' => now(),
            'total_amount' => 500,
        ]);

        $receipt->items()->create([
            'variant_id' => $variant->id,
            'quantity_received' => 5,
            'unit_cost' => 100,
            'line_total' => 500,
        ]);

        $this->actingAs($this->owner)
            ->get(route('admin.stock.index'))
            ->assertOk()
            ->assertSee('INV-9001')
            ->assertSee('Invoice Line Item');
    }

    public function test_consignment_batch_receipt_renders_its_lines(): void
    {
        $variant = Product::factory()->consignment()->create(['name' => 'Batched Blouse'])
            ->variants()->create(['stock_count' => 5]);

        $partner = ConsignmentPartner::factory()->create();

        $receipt = ConsignmentReceipt::query()->create([
            'partner_id' => $partner->id,
            'user_id' => $this->owner->id,
            'received_date' => now(),
        ]);

        $item = ConsignmentItem::query()->create([
            'consignment_receipt_id' => $receipt->id,
            'partner_id' => $receipt->partner_id,
            'variant_id' => $variant->id,
            'units_delivered' => 9,
            'agreed_base_price' => 200,
            'shop_markup' => 150,
            'received_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->get(route('admin.consignment.index'))
            ->assertOk()
            ->assertSee('Batched Blouse');

        $this->actingAs($this->owner)
            ->get(route('admin.consignment.items.pullout-form', $item))
            ->assertOk()
            ->assertSee('Pull Out');
    }

    public function test_consignment_item_edit_page_renders(): void
    {
        $item = ConsignmentItem::query()->firstOrFail();

        $this->actingAs($this->owner)
            ->get(route('admin.consignment.items.edit', $item))
            ->assertOk();
    }

    public function test_partner_pages_render(): void
    {
        $partner = ConsignmentPartner::query()->firstOrFail();

        $this->actingAs($this->owner)->get(route('admin.consignment.partners.create'))->assertOk();
        $this->actingAs($this->owner)->get(route('admin.consignment.partners.edit', $partner))->assertOk();
    }

    public function test_return_pages_render(): void
    {
        $item = SalesItem::query()->firstOrFail();

        $this->actingAs($this->owner)->get(route('admin.returns.index'))->assertOk();
        $this->actingAs($this->owner)->get(route('admin.returns.create', $item))->assertOk();
        $this->actingAs($this->owner)->get(route('admin.returns.search', ['q' => 'a']))->assertOk();
    }

    public function test_receipt_page_renders_when_a_receipt_exists(): void
    {
        $receipt = Receipt::query()->first();

        if (! $receipt) {
            $this->markTestSkipped('No receipt seeded.');
        }

        $this->actingAs($this->owner)->get(route('admin.receipts.show', $receipt))->assertOk();
    }

    public function test_payment_rows_render_on_the_order_detail_page(): void
    {
        $order = SalesTransaction::query()
            ->where('channel', 'online')
            ->whereHas('payments')
            ->firstOrFail();

        $this->actingAs($this->owner)
            ->get(route('admin.online.show', $order))
            ->assertOk();

        $this->assertInstanceOf(Payment::class, $order->payments->first());
    }
}
